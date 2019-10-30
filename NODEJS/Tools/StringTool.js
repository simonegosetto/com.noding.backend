class StringTool {

    isnull(string, value) {
        if (string === undefined || string === null) {
            if (value !== undefined && value !== null) {
                return value;
            }
            return '';
        }
        return string;
    }

}

module.exports = StringTool;
