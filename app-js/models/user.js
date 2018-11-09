export default class User {
    constructor(data = {}) {
        this.password = '********';
        this.groups = [];
        this.roles = [];
        if (Object.keys(data).length > 0) {
            this.userId = data.userId;
            this.name = data.name;
            this.username = data.username;
            this.roles = data.roles;
            this.groups = data.groups;
        }
    }

    getGroupUsers() {
        let users = [];
        for (let i in this.groups) {
            for (let j in this.groups[i].users) {
                users.push(this.groups[i].users[j]);
            }
        }

        return users;
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
            groups: this.groups,
        };
    }
}
