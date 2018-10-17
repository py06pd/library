export default class Author {
    constructor(data = {}) {
        if (Object.keys(data).length > 0) {
            this.authorId = data.authorId;
            this.forename = data.forename;
            this.surname = data.surname;
        }
    }

    getId () {
        return this.authorId;
    }

    getName () {
        if (this.surname) {
            return this.forename + ' ' + this.surname;
        }

        return this.forename;
    }

    getValue () {
        return this.authorId ? this.authorId : this.forename;
    }

    serialise () {
        return {
            authorId: this.authorId,
            forename: this.forename,
            surname: this.surname,
        };
    }
}
