export default class EvonUtilities {
    static debounce(func, wait) {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    static sanitizeInput(input) {
        return input.replace(/</g, "<").replace(/>/g, ">");
    }
}