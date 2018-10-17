import Author from '../models/author';
import Series from '../models/series';

export default class Book {
    constructor(data = {}) {
        this.authors = [];
        this.genres = [];
        this.series = [];
        this.users = [];
        if (Object.keys(data).length > 0) {
            this.bookId = data.bookId;
            this.name = data.name;
            this.type = data.type;
            this.authors = data.authors.map(x => new Author(x));
            this.genres = data.genres === '' ? [] : data.genres;
            this.series = data.series.map(x => new Series(x));
            this.users = data.users;
        }
    }

    getAuthors () {
        return this.authors;
    }

    getAuthorValues () {
        let authorIds = [];
        this.authors.forEach(x => authorIds.push(x.getValue()));
        return authorIds;
    }

    setAuthors (authors) {
        this.authors = authors;
        return this;
    }

    getGenres () {
        return this.genres;
    }

    getGiftedFrom (userId) {
        let user = this.users.find(x => x.userId === userId);
        if (user && user.giftedFrom) {
            return user.giftedFrom;
        }

        return null;
    }

    getId () {
        return this.bookId;
    }

    getName () {
        return this.name;
    }

    getNotes (userId) {
        let user = this.users.find(x => x.userId === userId);
        if (user) {
            return user.notes;
        }

        return null;
    }

    getOwnerNames () {
        let owners = [];
        this.users.map(function (x) {
            if (x.owned) {
                owners.push(x.name);
            }
        });

        return owners;
    }

    getReadByNames () {
        let read = [];
        this.users.map(function (x) {
            if (x.read) {
                read.push(x.name);
            }
        });

        return read;
    }

    getSeries () {
        return this.series;
    }

    getType () {
        return this.type;
    }

    hasBeenReadBy (userId) {
        if (this.users.find(x => x.userId === userId && x.read)) {
            return true;
        }

        return false;
    }

    hasOwner () {
        return this.users.find(x => x.owned);
    }

    isOwnedBy (userId) {
        if (this.users.find(x => x.userId === userId && x.owned)) {
            return true;
        }

        return false;
    }
    
    serialise () {
        return {
            bookId: this.bookId,
            name: this.name,
            type: this.type,
            authors: this.authors.map(x => x.serialise()),
            genres: this.genres,
            series: this.series.map(x => x.serialise()),
            users: this.users,
        };
    }
}
