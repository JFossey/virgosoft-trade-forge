// virgosoft-trade-forge/resources/js/utils/validationHelpers.js

export const validateDecimalPlaces = (value, maxDecimals = 8) => {
    if (value === null || value === undefined || value === '') return true; // Treat empty or null as valid for this check, assuming other checks handle 'required'
    const number = parseFloat(value);
    if (isNaN(number)) return false; // If not a valid number, it fails decimal place validation
    
    const str = value.toString(); // Use original string value to check decimals
    const decimalIndex = str.indexOf('.');
    
    if (decimalIndex === -1) {
        return true; // No decimal point, so no decimal places to validate
    }
    
    return str.length - decimalIndex - 1 <= maxDecimals;
};
