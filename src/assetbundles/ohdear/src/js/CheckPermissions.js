export default function (permissions, ability, check) {
    if (!permissions.hasOwnProperty(check)) {
        return false;
    }

    if (!permissions[check].hasOwnProperty(ability)) {
        return false;
    }

    return permissions[check][ability];
}
