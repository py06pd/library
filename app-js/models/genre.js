export default class Genre {
    constructor(data = {}) {
        if (Object.keys(data).length > 0) {
            this.genreId = data.genreId;
            this.name = data.name;
        }
    }

    getId () {
        return this.genreId;
    }

    getName () {
        return this.name;
    }

    getValue () {
        return this.genreId ? this.genreId : this.name;
    }

    serialise () {
        return {
            genreId: this.genreId,
            name: this.name,
        };
    }
}
