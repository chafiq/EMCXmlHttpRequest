/**
 * @author Chafiq El Mechrafi <chafiq.elmechrafi@gmail.com>
 */

function EMCXmlHttpRequest() {
    if ( EMCXmlHttpRequest.caller != EMCXmlHttpRequest.getInstance ) {
        throw new Error('EMCXmlHttpRequest can\'t be instanciated')
    }
}

EMCXmlHttpRequest.getInstance = function() {
    if ( typeof( this._instance ) == "undefined"
        || this._instance === null
        || !( this._instance instanceof EMCXmlHttpRequest )
    ) {
        this._instance = new EMCXmlHttpRequest();
    }
    
    return this._instance;
};

EMCXmlHttpRequest.error = function(msg){
    alert(msg);
};

EMCXmlHttpRequest.prototype.checkResponse = function(response) {
    if (    typeof( response ) != "object"
        ||  response == null || typeof(response.code) != "number"
        ||  ( response.code == 0 && typeof(response.data) == "undefined" )
        ||  ( response.code > 0 && typeof(response.error) != "string" )
    ) {
        throw new Error('response is invalid');
    }  
};

EMCXmlHttpRequest.prototype.execute = function(response, callback) {
    this.checkResponse(response);
    if ( response.code == 0 ) {
        if ( typeof( callback ) == "function" ) {
            return callback(response.data);
        } else {
            return response.data;
        }
    }
    EMCXmlHttpRequest.error(response.error + (response.code > 1 ? ' (code #' + response.code + ')' : ''));
	return null;
};

EMCXmlHttpRequest.prototype.post = function( route, data, method, dataType, callback ) {
    var result;
    var that = this;
    var request = $.ajax({
        type    : method,
        url     : route,
        data    : data,
        async   : typeof(callbackSuccess) == "function",
        dataType: dataType,
        success : function(response, textStatus, jqXHR) {
            result = that.execute(response, callback);
        },
        error : function(){
            EMCXmlHttpRequest.error( 'Request Execution Error' );
        }
    });
    
    return result;
};


EMCXmlHttpRequest.prototype.stream = function( route, data, method, dataType, successCallback, streamCallback ) {
    
    var result;
    
    var that = this;
    $.stream(route, {
        type:'http',
        dataType: dataType,
        enableXDR : true,
        reconnect : false,
		openData : data,
        context: EMCXmlHttpRequest.getStreamDialog('Enregistrement'),
        message: function(event, stream) {
            if( typeof( event.data ) == "object" ) {
				if ( typeof( event.data.stream ) == "object" ) {
					if( typeof(event.data.stream.percent) == "number" && typeof(event.data.stream.message) == "string" ) {
						$(this).trigger('update', event.data.stream);
					}
					
					if ( "data" in event.data.stream && typeof( streamCallback ) == "function" ) {
						try {
							streamCallback( event.data.stream.data );
						} catch( e ) {
							EMCXmlHttpRequest.error( 'Streaming Data Error' );
						}
					}
				} else {
                    result = that.execute(event.data, successCallback);
                }
            }
            
        },
        error: function() {
            EMCXmlHttpRequest.error( 'Request Execution Error' );
        },
        close: function() {
           $(this).dialog('destroy');
        }
    });
    
    return result;
};

EMCXmlHttpRequest.getStreamDialog = function(title) {
    var dialogClass = 'emc-dialog';
    
	if ( typeof( $.ui ) != "object" || typeof( $.ui.dialog ) != "function" ) {
		throw new Error('$.dialog not found');
	}
	
    var $dialog =  $(document.createElement('div'))
                        .addClass(dialogClass)
                        .attr('title', title)
                        .append(
                            $(document.createElement('div'))
                                .addClass(dialogClass + '-progress')
                                .append(
                                    document.createElement('div')
                                )
                                .append(
                                    $(document.createElement('span'))
                                        .text('0%')
                                )
                        )
                        .append(
                            $(document.createElement('div'))
                                .addClass(dialogClass + '-message')
                        )
                        .bind('update', function(event, data){
                            $(this).find('> .' + dialogClass + '-progress > div')
                                .css('width', data.percent + '%');

                            $(this).find('> .' + dialogClass + '-progress > span')
                                .text(data.percent + '%');

                            var $message = $(document.createElement('p'))
                                                .text(data.message);
                                                
                            $(this).find('> .' + dialogClass + '-message')
                                .append($message)
                                .animate({scrollTop: $message.offset().top});
                        });

    $('body').append($dialog);
    
    $dialog.dialog({modal: true});
    
    return $dialog.get(0);
};