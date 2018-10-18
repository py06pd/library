export default class User {
    constructor(data = {}) {
        this.groupUsers = [];
        this.roles = [];
        if (Object.keys(data).length > 0) {
            this.userId = data.userId;
            this.name = data.name;
            this.username = data.username;
            this.roles = data.roles;
            this.groupUsers = data.groupUsers;
        }
    }

    getGroupUsers() {
        return this.groupUsers;
    }

    getId() {
        return this.userId;
    }

    getName() {
        return this.name;
    }

    setName(name) {
        this.name = name;
        return this;
    }

    getUsername() {
        return this.username;
    }

    setUsername(username) {
        this.username = username;
        return this;
    }

    hasRole (role) {
        return this.roles.indexOf(role) !== -1;
    }

    serialise () {
        return {
            userId: this.userId,
            name: this.name,
            username: this.username,
            roles: this.roles,
            groupUsers: this.groupUsers,
        };
    }
}
