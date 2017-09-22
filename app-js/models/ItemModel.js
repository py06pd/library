var moment = require('moment');

export default class ItemModel {
    
    // Create a new ItemModel instance
    constructor(data, type, fieldDefinitions) {
        this.data = data;
        
        // if source is empty then set as empty object rather than array to prevent js errors
        if (this.data._source.length === 0) {
            this.data._source = {};
        }
        
        this.type = type;
        this.fieldDefinitions = fieldDefinitions;

        // Loop over the field definitions and convert any dates from String to Date
        for (var fieldName in this.fieldDefinitions) {
            if (this.fieldDefinitions.hasOwnProperty(fieldName)) {

                var field = this.fieldDefinitions[fieldName];

                if (field.input === 'date') {
                    if (typeof(this.data._source[fieldName]) === 'undefined' || this.data._source[fieldName] === '') {
                        this.data._source[fieldName] = null;
                    } else {
                        this.data._source[fieldName] = moment.utc(this.data._source[fieldName]);
                    }
                }
            }
        }
        
        this.initialData = JSON.parse(JSON.stringify(data));
        this.changed = [];
    }
    
    // Get the ID of this item
    getId() {
        return this.data._id;
    }
    
    // Get the value of field
    getFieldValue(field) {
        //for users get string value
        if (this.getFieldInputType(field, true) === 'userSelect' && typeof(this.data._source[field]) !== 'undefined') {
            this.data._source[field] = this.data._source[field].toString();
        }
        if (this.getFieldInputType(field, true) === 'userMultiselect' && typeof(this.data._source[field]) !== 'undefined') {
            for (var i in this.data._source[field]) {
                this.data._source[field][i] = this.data._source[field][i].toString();
            }
        }
        
        if (typeof(this.data._source[field]) === 'undefined') {
            if (['multiselect', 'itemMultiselect', 'userMultiselect'].indexOf(this.getFieldInputType(field, true)) !== -1) {
                this.data._source[field] = [];
            } else {
                this.data._source[field] = '';
            }
        }
        
        // if checkbox then return boolean
        if (this.getFieldInputType(field, true) === 'checkbox' && this.data._source[field] === '') {
            this.data._source[field] = false;
        }
        
        // simply return the value straight from our data variable.
        return this.data._source[field];
    }
        
    setFieldValue(field, value) {
        
        this.data['_source'][field] = value;
        
        // Remove this field from the changed array and we'll figure out
        // if we need to add it back later.
        for (var i in this.changed) {
            if (this.changed[i] === field) {
                this.changed.splice(i, 1);
                break;
            }
        }
        
        // If the value is undefined then set it to null so that the equality check below works
        // for empty fields
        if (typeof value === 'undefined') {
            value = null;
        }
        
        if (this.initialData._source[field] !== value) {
            // The field has changed so add it to the list of changed files
            this.changed.push(field);
        }
    }
    
    getData() {
        return this.data._source;
    }
    
    // Return true if this item has been changed, false if it hasn't
    hasChanged() {
        return this.changed.length > 0;
    }
        
    // Returns the given field's type
    getFieldInputType(field, resolveReferenceFields = false) {
        
        if (resolveReferenceFields) {
            if (this.fieldDefinitions[field].input === 'reference') {
                return this.fieldDefinitions[field].refInput;
            }
        }
        
        return this.fieldDefinitions[field].input;
    }
    
    // Return the field definitions for the given type
    getFieldDefinitions() {
        return this.fieldDefinitions;
    }
    
    // Return the type of the item that 'field' is a relationship to
    getReferredType(field) {
        
        switch (this.getFieldInputType(field)) {
            case 'itemSelect':
            case 'itemMultiselect':
                return this.fieldDefinitions[field].itemType;
            case 'userSelect':
            case 'userMultiselect':
                return 'users';
            default:
                return;
        }
    }

    getFieldLabel(field) {
        return this.fieldDefinitions[field].label;
    }
    
    // Return an array containing all the labels needed to display
    // field's current values.
    getLabels(field) {
        var options = this.getOptionsForField(field);
        var labels = [];
        for (var i in options) {
            labels[options[i].value] = options[i].label;
        }
        
        return labels;
    }
    
    getOptionsForField(field) {
      
        var options = [];
        
        var fieldType = this.getFieldInputType(field, true);
                    
        switch (fieldType) {
            case 'select':
            case 'multiselect':
            case 'userSelect':
            case 'userMultiselect':
            case 'itemSelect':
            case 'itemMultiselect':
                options = this.fieldDefinitions[field].options;
                break;
        }
      
        return options;
    }
    
    // change referenced item data when linked field value is changed
    setReferenceFields(data, labels) {
        // set data for reference field
        for (var i in data) {
            var input = this.getFieldInputType(i, true);
            if (data[i] === null) {
                if (input.substring(input.length - 11).toLowerCase() === 'multiselect') {
                    this.data._source[i] = [];
                } else {
                    this.data._source[i] = '';
                }
            } else {
                this.data._source[i] = data[i];
            }
        }
        // set labels for select boxes
        for (var j in labels) {
            this.fieldDefinitions[j].options = labels[j];
        }
    }
    
    getNotes() {
        return this.data._notes;
    }
}
