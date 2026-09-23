/**
 * Formatting utilities for the EVON dashboard
 */

/**
 * Format number with K suffix for thousands
 * @param {number} num - Number to format
 * @returns {string} Formatted number
 */
export const formatNumber = (num) => {
  if (num >= 1000) {
    return `${(num / 1000).toFixed(0)}K`
  }
  return num.toString()
}

/**
 * Format KWH value
 * @param {number} kwh - KWH value
 * @returns {string} Formatted KWH
 */
export const formatKWH = (kwh) => {
  return kwh.toString()
}

/**
 * Format time in hours
 * @param {number} hour - Hour value (0-23)
 * @returns {string} Formatted time (e.g., "03h")
 */
export const formatTime = (hour) => {
  return `${hour.toString().padStart(2, '0')}h`
}

/**
 * Format currency
 * @param {number} amount - Amount to format
 * @param {string} currency - Currency code (default: 'EUR')
 * @returns {string} Formatted currency
 */
export const formatCurrency = (amount, currency = 'EUR') => {
  const locale = currency === 'EUR' ? 'fr-FR' : (currency === 'USD' ? 'en-US' : 'fr-MA')
  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency: currency
  }).format(amount)
}

/**
 * Format date
 * @param {string|Date} value - Date to format
 * @returns {string} Formatted date
 */
export const formatDate = (value) => {
  return new Date(value).toLocaleDateString('fr-FR')
}

/**
 * Format date and time
 * @param {string|Date} value - Date to format
 * @returns {string} Formatted date and time
 */
export const formatDateTime = (value) => {
  return new Date(value).toLocaleString('fr-FR')
}

/**
 * Format percentage
 * @param {number} value - Value to format as percentage
 * @param {number} decimals - Number of decimal places (default: 1)
 * @returns {string} Formatted percentage
 */
export const formatPercentage = (value, decimals = 1) => {
  return `${value.toFixed(decimals)}%`
}

/**
 * Composable for using formatters
 */
export const useFormatters = () => {
  return {
    formatNumber,
    formatKWH,
    formatTime,
    formatCurrency,
    formatDate,
    formatDateTime,
    formatPercentage
  }
}