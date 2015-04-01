/**
 * @author Chafiq El Mechrafi <chafiq.elmechrafi@gmail.com>
 */

function EMCXmlHttpRequest() {
    if (EMCXmlHttpRequest.caller != EMCXmlHttpRequest.getInstance) {
        throw new Error('EMCXmlHttpRequest can\'t be instanciated')
    }
}

EMCXmlHttpRequest.HEADER_TAG = 'X-EMC-XmlHttpRequest';

EMCXmlHttpRequest.STREAM_DELIMITER = "\n";

EMCXmlHttpRequest.EVENT_STREAM_PROGRESS = 'emc.xmlhttprequest.stream.progress';
EMCXmlHttpRequest.EVENT_STREAM_SUCCESS = 'emc.xmlhttprequest.stream.success';
EMCXmlHttpRequest.EVENT_STREAM_ERROR = 'emc.xmlhttprequest.stream.error';
EMCXmlHttpRequest.EVENT_INFO = 'emc.xmlhttprequest.info';
EMCXmlHttpRequest.EVENT_STREAM_UPLOAD = 'emc.xmlhttprequest.stream.upload';
EMCXmlHttpRequest.EVENT_STREAM_UPLOAD_DONE = 'emc.xmlhttprequest.stream.upload.done';

EMCXmlHttpRequest.DATA_TYPE_JSON = 'JSON';
EMCXmlHttpRequest.DATA_TYPE_JSONP = 'JSONP';
EMCXmlHttpRequest.DATA_TYPE_HTML = 'HTML';
EMCXmlHttpRequest.DATA_TYPE_XML = 'XML';
EMCXmlHttpRequest.DATA_TYPE_TEXT = 'TEXT';

EMCXmlHttpRequest.METHOD_GET = 'GET';
EMCXmlHttpRequest.METHOD_POST = 'POST';
EMCXmlHttpRequest.METHOD_DELETE = 'DELETE';

EMCXmlHttpRequest.ERROR_INVALID_RESPONSE = -1;
EMCXmlHttpRequest.ERROR_REQUEST = -2;

EMCXmlHttpRequest.availableMethods = [
    EMCXmlHttpRequest.METHOD_GET,
    EMCXmlHttpRequest.METHOD_POST,
    EMCXmlHttpRequest.METHOD_DELETE
];

EMCXmlHttpRequest.availableTypes = [
    EMCXmlHttpRequest.DATA_TYPE_JSON,
    EMCXmlHttpRequest.DATA_TYPE_TEXT,
    EMCXmlHttpRequest.DATA_TYPE_JSONP,
    EMCXmlHttpRequest.DATA_TYPE_HTML,
    EMCXmlHttpRequest.DATA_TYPE_XML
];


EMCXmlHttpRequest.getInstance = function() {
    if (typeof (this._instance) == "undefined"
            || this._instance === null
            || !(this._instance instanceof EMCXmlHttpRequest)
            ) {
        this._instance = new EMCXmlHttpRequest();
    }

    return this._instance;
};


EMCXmlHttpRequest.prototype.getHeaders = function(type) {
    var headers = {};
    headers[ EMCXmlHttpRequest.HEADER_TAG ] = type;
    return headers;
};

EMCXmlHttpRequest.error = function(code, message) {
    throw new EMCXmlHttpRequestError(code, message);
};

EMCXmlHttpRequest.prototype.checkResponse = function(response) {
    if (typeof (response) != "object"
            || response == null || typeof (response.code) != "number"
            || (response.code == 0 && typeof (response.data) == "undefined")
            || (response.code > 0 && typeof (response.error) != "string")
            ) {
        EMCXmlHttpRequest.error(EMCXmlHttpRequest.ERROR_INVALID_RESPONSE, 'response is invalid');
    }
};

EMCXmlHttpRequest.prototype.execute = function(response, callback, dataType) {

    this.checkResponse(response);
    var data = response.data;

    if (typeof (callback) === "function") {
        try {
            return callback(data, typeof (response.info) === "object" ? response.info : {});
        } catch (error) {
        }
    }

    return data;
};

EMCXmlHttpRequest.prototype.debug = function(xhr) {
    // Get the xdebugToken from response headers

    // If the Sfjs object exists
    if (typeof Sfjs !== "undefined") {
        var xdebugToken = xhr.getResponseHeader('X-Debug-Token');

        if (typeof (xdebugToken) !== "string") {
            return;
        }
        // Grab the toolbar element
        var currentElement = $('.sf-toolbar')[0];
        // Load the data of the given xdebug token into the current toolbar wrapper
        Sfjs.load(currentElement.id, currentElement.dataset.sfurl.replace(/_wdt\/.*/, '_wdt/' + xdebugToken));
    }
};

EMCXmlHttpRequest.prototype.ajax = function(method, route, data, callback, dataType, headerTag) {

    if (typeof (method) !== "string" || EMCXmlHttpRequest.availableMethods.indexOf(method) < 0) {
        throw new Error('EMCXmlHttpRequest.ajax: Invalid Argument "method"');
    }

    if (typeof (dataType) !== "string") {
        dataType = EMCXmlHttpRequest.DATA_TYPE_JSON;
    } else if (EMCXmlHttpRequest.availableTypes.indexOf(dataType) < 0) {
        throw new Error('EMCXmlHttpRequest.ajax: Invalid Argument "dataType"');
    }

    if (typeof (data) === "undefined" || data === null) {
        data = {};
    } else if (typeof (data) !== "object") {
        throw new Error('EMCXmlHttpRequest.ajax: Invalid Argument "data"');
    }

    var headers = this.getHeaders(method);
    if (typeof (headerTag) === "string") {
        headers[headerTag] = 1;
    }

    var result;
    var that = this;
    var request = $.ajax({
        type: method,
        url: route,
        data: data,
        async: typeof (callback) === "function",
        dataType: EMCXmlHttpRequest.DATA_TYPE_JSON,
        headers: headers,
        success: function(response, status, xhr) {
            try {
                if (xhr.getResponseHeader(EMCXmlHttpRequest.HEADER_TAG) === null) {
                    throw new Error('Response not valid : "' + EMCXmlHttpRequest.HEADER_TAG + '" header tag not found');
                }
                result = that.execute(response, callback, dataType);

                that.debug(xhr);
            }
            catch (error) {
                EMCXmlHttpRequest.error(EMCXmlHttpRequest.ERROR_REQUEST, 'Request Execution Error : ' + error.message);
            }
        },
        error: function(xhr) {
            that.debug(xhr);
            EMCXmlHttpRequest.error(EMCXmlHttpRequest.ERROR_REQUEST, 'Request Execution Error');
        }
    });

    return result;
};

EMCXmlHttpRequest.prototype.get = function(route, data, callback, dataType, headerTag) {
    return this.ajax(EMCXmlHttpRequest.METHOD_GET, route, data, callback, dataType, headerTag);
};

EMCXmlHttpRequest.prototype.post = function(route, data, callback, dataType, headerTag) {
    return this.ajax(EMCXmlHttpRequest.METHOD_POST, route, data, callback, dataType, headerTag);
};

EMCXmlHttpRequest.prototype.delete = function(route, data, callback, dataType, headerTag) {
    return this.ajax(EMCXmlHttpRequest.METHOD_DELETE, route, data, callback, dataType, headerTag);
};

EMCXmlHttpRequest.prototype.stream = function(route, data, callback, method, context, params) {

    var that = this;

    var lastChunkLength = 0;

    var handlerCallback = function(response) {
        if (typeof (response) !== "object" || typeof (response.code) !== "number") {
            EMCXmlHttpRequest.error(EMCXmlHttpRequest.ERROR_INVALID_RESPONSE, 'response is invalid');
        }

        if (response.code !== 0) {
            $(context).trigger(EMCXmlHttpRequest.EVENT_STREAM_ERROR, [response.code, response.error]);
            EMCXmlHttpRequest.error(response.code, response.error);
        }

        if (typeof (response.stream) === "object") {
            $(context).trigger(EMCXmlHttpRequest.EVENT_STREAM_PROGRESS, [response.stream]);
        }
        else if (typeof (response.data) !== "undefined") {
            $(context).trigger(EMCXmlHttpRequest.EVENT_STREAM_SUCCESS, [response.data]);
            that.execute(response, callback, EMCXmlHttpRequest.DATA_TYPE_JSON);
        }

        return true;
    };

    var onProgress = function(response, xhr, length) {
        try {
            response = response.substring(lastChunkLength, length);

            var delimiter = xhr.getResponseHeader(EMCXmlHttpRequest.HEADER_TAG);

            if (delimiter === null) {
                throw new Error('Response not valid : "' + EMCXmlHttpRequest.HEADER_TAG + '" header tag not found');
            }

            var responses = response.split(delimiter + "\n");
            for (var i = 0; i < responses.length; i++) {
                var _response = null;
                try {
                    _response = $.parseJSON(responses[i]);
                    lastChunkLength += responses[i].length + delimiter.length + 1;
                } catch (error) {
                    return;
                }

                handlerCallback(_response);
            }

        } catch (error) {
            EMCXmlHttpRequest.error(EMCXmlHttpRequest.ERROR_REQUEST, 'Request Execution Error : ' + error.message);
        }
    };

    if (typeof (params) !== "object") {
        params = {};
    }

    var request = $.ajax($.extend({
        type: method,
        url: route,
        data: data,
        async: true,
        dataType: EMCXmlHttpRequest.DATA_TYPE_TEXT,
        headers: this.getHeaders('STREAM'),
        xhr: function() {
            // get the native XmlHttpRequest object
            var xhr = $.ajaxSettings.xhr();

            // set the onprogress event handler
            xhr.upload.onprogress = function(event) {
                $(context).trigger(EMCXmlHttpRequest.EVENT_STREAM_UPLOAD, [event.loaded * 100 / event.total]);
            };

            // set the onload event handler
            xhr.upload.onload = function() {
                $(context).trigger(EMCXmlHttpRequest.EVENT_STREAM_UPLOAD_DONE);
            };

            xhr.upload.onloadend = xhr.upload.onload;

            return xhr;
        },
        xhrFields: {
            onprogress: function(event) {
                onProgress(event.target.responseText, event.target, event.loaded);
            }
        },
        success: function(response, status, xhr) {
            onProgress(response, xhr, response.length);
            that.debug(xhr);
        },
        error: function(xhr) {
            $(context).trigger(EMCXmlHttpRequest.EVENT_STREAM_ERROR, ['Request Execution Error']);
            that.debug(xhr);
            EMCXmlHttpRequest.error(EMCXmlHttpRequest.ERROR_REQUEST, 'Request Execution Error');
        }
    }, params));
};

/****************************************************/
/************ EMCXmlHttpRequestError ****************/
/****************************************************/

/**
 * Class exception for EMCXmlHttpRequest
 * @param integer code
 * @param string message
 * @param mixed data
 * @returns {EMCXmlHttpRequestError}
 */
function EMCXmlHttpRequestError(code, message, data) {
    Error.call(this);
    this.code = code;
    this.data = data;
    this.message = message;
}

EMCXmlHttpRequestError.prototype = Error.prototype;
EMCXmlHttpRequestError.prototype.constructor = EMCXmlHttpRequestError;
EMCXmlHttpRequestError.prototype.name = 'EMCXmlHttpRequestError';