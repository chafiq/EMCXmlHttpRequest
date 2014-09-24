/**
 * @author Chafiq El Mechrafi <chafiq.elmechrafi@gmail.com>
 */

function EMCXmlHttpRequest() {
    if ( EMCXmlHttpRequest.caller != EMCXmlHttpRequest.getInstance ) {
        throw new Error('EMCXmlHttpRequest can\'t be instanciated')
    }
}

EMCXmlHttpRequest.EVENT_STREAM_PROGRESS = 'emc.stream.progress';
EMCXmlHttpRequest.EVENT_STREAM_SUCCESS = 'emc.stream.success';
EMCXmlHttpRequest.EVENT_STREAM_ERROR = 'emc.stream.error';

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

    var data = response;
    if (dataType.toLowerCase() !== 'html') {
        this.checkResponse(response);
        data = response.data;
    }

    if (typeof (callback) == "function") {
        return callback(data);
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

EMCXmlHttpRequest.prototype.ajax = function(method, route, data, callback, dataType) {

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

    var result;
    var that = this;
    var request = $.ajax({
        type: method,
        url: route,
        data: data,
        async: typeof (callback) === "function",
        dataType: dataType,
        success: function(response, status, xhr) {
            try {
                result = that.execute(response, callback, dataType);
                that.debug(xhr);
            }
            catch (error) {
                EMCXmlHttpRequest.error(EMCXmlHttpRequest.ERROR_REQUEST, 'Request Execution Error');
            }
        },
        error: function(xhr) {
            that.debug(xhr);
            EMCXmlHttpRequest.error(EMCXmlHttpRequest.ERROR_REQUEST, 'Request Execution Error');
        }
    });

    return result;
};

EMCXmlHttpRequest.prototype.get = function(route, data, callback, dataType) {
    return this.ajax(EMCXmlHttpRequest.METHOD_GET, route, data, callback, dataType);
};

EMCXmlHttpRequest.prototype.post = function(route, data, callback, dataType) {
    return this.ajax(EMCXmlHttpRequest.METHOD_POST, route, data, callback, dataType);
};

EMCXmlHttpRequest.prototype.delete = function(route, data, callback, dataType) {
    return this.ajax(EMCXmlHttpRequest.METHOD_DELETE, route, data, callback, dataType);
};

EMCXmlHttpRequest.prototype.stream = function(route, data, callback, method, context) {

    var that = this;

    var lastChunkLength = 0;

    var handlerCallback = function( response ) {
        if (typeof (response) != "object" || typeof (response.code) != "number" || response.code > 0) {
            EMCXmlHttpRequest.error(EMCXmlHttpRequest.ERROR_INVALID_RESPONSE, 'response is invalid');
        }

        if (typeof (response.stream) == "object") {
            $(context).trigger(EMCXmlHttpRequest.EVENT_STREAM_PROGRESS, response.stream);
        }
        else if (typeof (response.data) == "object") {
            $(context).trigger(EMCXmlHttpRequest.EVENT_STREAM_SUCCESS, response.data);
            that.execute(response, callback, EMCXmlHttpRequest.DATA_TYPE_JSON);
        }
        
        return true;
    };

    var request = $.ajax({
        type: method,
        url: route,
        data: data,
        async: true,
        dataType: EMCXmlHttpRequest.DATA_TYPE_TEXT,
        xhrFields: {
            onprogress: function(event) {
                try {
                    var data = event.target.responseText.substring(lastChunkLength, event.loaded);
                    
                    var responses = data.split("\n").filter(function(item){return item.replace(" ", "").length>0;});
                    
                    for ( var response=0; response < responses.length; response++ ) {
                        handlerCallback( $.parseJSON(responses[response]) );
                    }
                    
                    lastChunkLength = event.loaded;
                }
                catch (error) {
                    $(context).trigger(EMCXmlHttpRequest.EVENT_STREAM_ERROR, 'Request Execution Error', error);
                    EMCXmlHttpRequest.error(EMCXmlHttpRequest.ERROR_REQUEST, 'Request Execution Error');
                }
            }
        },
        error: function(xhr) {
            $(context).trigger(EMCXmlHttpRequest.EVENT_STREAM_ERROR, 'Request Execution Error');
            that.debug(xhr);
            EMCXmlHttpRequest.error(EMCXmlHttpRequest.ERROR_REQUEST, 'Request Execution Error');
        }
    });
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
    Error.call(this, message);
    this.code = code;
    this.data = data;
}

EMCXmlHttpRequestError.prototype = Error.prototype;
EMCXmlHttpRequestError.prototype.constructor = EMCXmlHttpRequestError;
EMCXmlHttpRequestError.prototype.name = 'EMCXmlHttpRequestError';