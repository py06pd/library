import { Loading } from 'element-ui';

module.exports = {
    data: function () {
        return {
            loading: null,
        };
    },
    methods: {
        // convert object to url encoded string
        objectToUrlParams: function(data) {
            var body = Object.keys(data).map(function(k) {
                if (data[k] instanceof Array) {
                    // if array then parse to urlencoded array eg. a:['b','c'] to a[0]=b&a[1]=c
                    var parts = [];
                    for (var i in data[k]) {
                        parts.push(encodeURIComponent(k + '[' + i.toString() + ']') + '=' + encodeURIComponent(data[k][i]));
                    }
                    return parts.join('&');
                } else {
                    // if not array then just join key and value with =
                    return encodeURIComponent(k) + '=' + encodeURIComponent(data[k]);
                }
            }).join('&');

            return body;
        },
        // clear any notifications
        clearNotifications: function() {
            var notifications = document.getElementsByClassName('el-notification');
            while (notifications.length > 0) {
                notifications.item(0).parentElement.removeChild(notifications.item(0));
            }
        },
        // clear loading overlay
        clearStatus: function() {
            this.loading.close();
        },
        // post with deleting indicator
        delete: function(target, params) {
            var promise = this.load(target, params, 'Deleting...');
            
            promise.then(function(response) {
                if (response.body.status === 'OK') {
                    this.showSucessMessage('Delete successful');
                } else if (response.body.status === 'error') {
                    this.showErrorMessage(response.body.errorMessage);
                }
            });
            
            return promise;
        },
        // post with loading indicator
        load: function(target, params, text) {
            this.clearNotifications();
            this.showStatus(text);
            var promise = this.post(target, params);
            promise.then(function(response) {
                if (typeof(response.body.status) !== 'undefined') {
                    this.clearStatus();
                }
            });
            
            return promise;
        },
        // perform http post and return promise
        post: function(target, params) {
            // if object then convert to url encoded string
            if (typeof(params) === 'object') {
                params = this.objectToUrlParams(params);
            }
            
            // return promise so success and failure can be handled by caller
            var promise = this.$http.post(
                target,
                params,
                {'headers':{'Content-type':'application/x-www-form-urlencoded'}}
            );
    
            promise.then(function(response) {
                if (typeof(response.body.status) === 'undefined') {
                    // if no status in response then session may have expired - reload page to trigger redirect to login page
                    window.location.href = 'login';
                }
            }, function(response) {
                if (response.status === 403) {
                    this.showAccessDeniedMessage();
                    return;
                }
                this.showErrorMessage(response.statusText);
            });
    
            return promise;
        },
        // post with saving indicator
        save: function(target, params) {
            var promise = this.load(target, params, 'Saving...');
            promise.then(function(response) {
                if (response.body.status === 'OK') {
                    this.showSucessMessage('Update successful');
                } else if (response.body.status === 'error') {
                    this.showErrorMessage(response.body.errorMessage);
                }
            });
            
            return promise;
        },
        // show access denied message
        showAccessDeniedMessage: function () {
            this.$notify({ title: 'Access Denied', message: 'You do not have sufficient rights to access this page', type: 'warning', offset: 85 });
        },
        // show error message for unsuccessful action (eg. save/ delete)
        showErrorMessage: function(text) {
            this.$notify({ title: 'Error', message: text, type: 'error', offset: 85 });
        },
        // show loading overlay
        showStatus: function(text) {
            var loadingText = 'Loading...';
            if (typeof(text) !== 'undefined') {
                loadingText = text;
            }
            
            this.loading = Loading.service({ fullscreen: true, text: loadingText });
        },
        // show update successful message for action (eg. save/ delete) success
        showSucessMessage: function(text) {
            this.$notify({ title: 'Success', message: text, type: 'success', offset: 85 });
        },
    },
};
