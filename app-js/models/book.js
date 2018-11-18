import Author from '../models/author';
import Genre from '../models/genre';
import Series from '../models/series';
import Type from '../models/type';

export default class Book {
    constructor(data = {}) {
        this.authors = [];
        this.genres = [];
        this.series = [];
        this.users = [];
        if (Object.keys(data).length > 0) {
            this.bookId = data.bookId;
            this.name = data.name;
            this.type = data.type ? new Type(data.type) : null;
            this.creatorId = data.creatorId;
            this.authors = data.authors.map(x => new Author(x));
            this.genres = data.genres.map(x => new Genre(x));
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

    getBorrowedFrom (userId) {
        return this.users.filter(x => x.borrowedFromId && x.borrowedFromId === userId);
    }

    getBorrowedBy (userId) {
        let user = this.users.find(x => x.borrowedFromId && x.userId === userId);
        return user.borrowedFrom;
    }

    getBorrowedTime (userId) {
        let user = this.users.find(x => x.borrowedFromId && x.userId === userId);
        return user.borrowedTime;
    }

    getGenres () {
        return this.genres;
    }

    getGenreNames () {
        return this.genres.map(x => x.name);
    }

    getGenreValues () {
        let genreIds = [];
        this.genres.forEach(x => genreIds.push(x.getValue()));
        return genreIds;
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

    getRequestedFrom (userId) {
        return this.users.filter(x => x.requestedFromId && x.requestedFromId === userId);
    }

    getRequestedBy (userId) {
        let user = this.users.find(x => x.requestedFromId && x.userId === userId);
        return user.requestedFrom;
    }

    getRequestedTime (userId) {
        let user = this.users.find(x => x.requestedFromId && x.userId === userId);
        return user.requestedtime;
    }

    getSeries () {
        return this.series;
    }

    getSeriesById (seriesId) {
        let series = this.series.find(x => x.getId() === seriesId);
        if (series) {
            return series;
        }

        return null;
    }

    getType () {
        return this.type;
    }

    getTypeName () {
        return this.type ? this.type.getName() : null;
    }

    getTypeValue () {
        return this.type ? this.type.getValue() : null;
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

    isOnlyUser (userId) {
        return (this.creatorId === userId && (this.users.length === 0 || (
            this.users.length === 1 && this.users[0].userId === userId
        )));
    }

    isOwnedBy (userId) {
        if (this.users.find(x => x.userId === userId && x.owned)) {
            return true;
        }

        return false;
    }

    canRequest (userId) {
        if (this.users.find(x => x.userId === userId && x.requestedFrom)) {
            return false;
        }

        return true;
    }

    isOnWishlist (userId) {
        if (this.users.find(x => x.userId === userId && x.wishlist)) {
            return true;
        }

        return false;
    }

    setAuthors (authors) {
        this.authors = authors;
        return this;
    }

    setGenres (genres) {
        this.genres = genres;
        return this;
    }

    setType (type) {
        this.type = type;
        return this;
    }

    serialise () {
        return {
            bookId: this.bookId,
            name: this.name,
            type: this.type ? this.type.serialise() : null,
            authors: this.authors.map(x => x.serialise()),
            genres: this.genres.map(x => x.serialise()),
            series: this.series.map(x => x.serialise()),
            users: this.users,
        };
    }
}
