export default class User {
    constructor(data = {}) {
        this.users = [];
        if (Object.keys(data).length > 0) {
            this.groupId = data.groupId;
            this.name = data.name;
            this.users = data.users;
        }
    }

    getId() {
        return this.groupId;
    }

    getName() {
        return this.name;
    }

    setName(name) {
        this.name = name;
        return this;
    }

    getUsers () {
        return this.users;
    }

    serialise () {
        return {
            groupId: this.groupId,
            name: this.name,
            users: this.users,
        };
    }
}
