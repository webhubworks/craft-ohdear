export default function (languageLines, key, replace = {}) {
    let translation = languageLines[key] ? languageLines[key] : key

    Object.keys(replace).forEach(function (key) {
        translation = translation.replace('{' + key + '}', replace[key])
    })

    return translation
}
