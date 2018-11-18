export default class Type {
    constructor(data = {}) {
        if (Object.keys(data).length > 0) {
            this.typeId = data.typeId;
            this.name = data.name;
        }
    }

    getId () {
        return this.typeId;
    }

    getName () {
        return this.name;
    }

    getValue () {
        return this.typeId ? this.typeId : this.name;
    }

    serialise () {
        return {
            typeId: this.typeId,
            name: this.name,
        };
    }
}
