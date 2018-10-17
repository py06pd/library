export default class Series {
    constructor(data = {}) {
        if (Object.keys(data).length > 0) {
            this.seriesId = data.seriesId;
            this.name = data.name;
            this.number = data.number;
        }
    }

    getId () {
        return this.seriesId;
    }

    getName () {
        return this.name;
    }

    getNumber () {
        return this.number;
    }

    getValue () {
        return this.seriesId ? this.seriesId : this.name;
    }

    serialise () {
        return {
            seriesId: this.seriesId,
            name: this.name,
            number: this.number,
        };
    }
}
