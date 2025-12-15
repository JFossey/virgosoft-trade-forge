// virgosoft-trade-forge/resources/js/utils/validationHelpers.js

export const validateDecimalPlaces = (value, maxDecimals = 8) => {
    // Treat empty or null as valid for this check, assuming other checks handle 'required'
    if (value === null || value === undefined || value === '') {
        return true;
    }

    const number = parseFloat(value);

    // If not a valid number, it fails decimal place validation
    if (isNaN(number)) {
        return false;
    }

    // Use original string value to check decimals
    const str = value.toString();
    const decimalIndex = str.indexOf('.');

    // No decimal point, so no decimal places to validate
    if (decimalIndex === -1) {
        return true;
    }

    return str.length - decimalIndex - 1 <= maxDecimals;
};
