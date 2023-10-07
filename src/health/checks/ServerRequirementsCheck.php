<?php

namespace webhubworks\ohdear\health\checks;

use craft\db\Connection;
use craft\helpers\Db;
use OhDear\HealthCheckResults\CheckResult;

class ServerRequirementsCheck extends Check
{
    private bool $warnForOptionalRequirements = true;
    private array $summary;
    private array $requirements;

    public function warnForOptionalRequirements(bool $warn = true): self
    {
        $this->warnForOptionalRequirements = $warn;

        return $this;
    }

    public function run(): CheckResult
    {
        $this->checkRequirements();

        $result = (new CheckResult(
            name: 'Requirements',
            label: 'Server requirements',
            shortSummary: $this->summary['total'] - $this->summary['errors'] - $this->summary['warnings'] . ' of ' . $this->summary['total'] . ' requirements met',
            meta: [
                'requirements' => array_column($this->requirements, 'name'),
            ],
        ));

        if ($this->summary['errors'] > 0) {
            return $result->status(CheckResult::STATUS_FAILED)
                ->notificationMessage('Some mandatory requirements are not met.');
        }

        if ($this->summary['warnings'] > 0 && $this->warnForOptionalRequirements) {
            return $result->status(CheckResult::STATUS_WARNING)
                ->notificationMessage('Some optional requirements are not met.');
        }

        return $result->status(CheckResult::STATUS_OK)
            ->notificationMessage('The server meets all requirements.');
    }

    private function checkRequirements(): void
    {
        $reqCheck = new \RequirementsChecker();
        $dbConfig = \Craft::$app->getConfig()->getDb();
        $reqCheck->dsn = $dbConfig->dsn;
        $reqCheck->dbDriver = $dbConfig->dsn ? Db::parseDsn($dbConfig->dsn, 'driver') : Connection::DRIVER_MYSQL;
        $reqCheck->dbUser = $dbConfig->user;
        $reqCheck->dbPassword = $dbConfig->password;
        $reqCheck->checkCraft();

        $this->summary = $reqCheck->getResult()['summary'];
        $this->requirements = $reqCheck->getResult()['requirements'];
    }
}
