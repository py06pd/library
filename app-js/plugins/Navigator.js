export default class Navigator {
    
    constructor () {
        this.messageBox = null;
        this.router = null;
        this.saveWarning = false;
        this.saveWarningText = 'Are you sure you want to close the form? Any unsaved changes will be lost.';
    }
    
    go(target) {
        if (!this.saveWarning) {
            this.router.go(target);
            this.saveWarning = false;
        } else {
            var self = this;
            this.messageBox.confirm(this.saveWarningText, 'Confirm', {
                confirmButtonText: 'OK',
                cancelButtonText: 'Cancel',
                type: 'warning',
            }).then(function() {
                self.router.go(target);
                self.saveWarning = false;
            }).catch(function() {});
        }
    }
  
    install(Vue, options) {
        this.messageBox = options.messageBox;
        this.router = options.router;
        Vue.prototype.$navigator = this;
    }
    
    push(target) {
        if (!this.saveWarning) {
            this.router.push(target);
            this.saveWarning = false;
        } else {
            var self = this;
            this.messageBox.confirm(this.saveWarningText, 'Confirm', {
                confirmButtonText: 'OK',
                cancelButtonText: 'Cancel',
                type: 'warning',
            }).then(function() {
                self.router.push(target);
                self.saveWarning = false;
            }).catch(function() {});
        }
    }
            
    warn(warn) {
        this.saveWarning = warn;
        if (this.saveWarning) {
            window.onbeforeunload = function(e) {
                e.returnValue = this.saveWarningText;
                return this.saveWarningText;
            };
        } else {
            window.onbeforeunload = null;
        }
    }
}
