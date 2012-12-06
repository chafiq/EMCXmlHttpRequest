/**
 * @author Chafiq El Mechrafi <chafiq.elmechrafi@gmail.com>
 */

function EMCXmlHttpRequest() {
    if ( EMCXmlHttpRequest.caller != EMCXmlHttpRequest.getInstance ) {
        throw new Error('EMCXmlHttpRequest can\'t be instanciated')
    }
}

EMCXmlHttpRequest.getInstance = function(){
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

EMCXmlHttpRequest.prototype.checkResponse = function(response)
{
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
};

EMCXmlHttpRequest.prototype.post = function( route, data, dataType, callbackSuccess ) {
    var result;
    var that = this;
    var request = $.ajax({
        type    : 'POST',
        url     : route,
        data    : data,
        async   : typeof(callbackSuccess) == "function",
        dataType: dataType,
        success : function(response, textStatus, jqXHR) {
            result = that.execute(response, callbackSuccess);
        },
        error : function(){
            EMCXmlHttpRequest.error( 'Request Execution Error' );
        }
    });
    
    return result;
};


EMCXmlHttpRequest.prototype.stream = function( route, data, dataType, callbackSuccess ) {
    
    var result;
    
    var that = this;
    $.stream(route, {
        type:'http',
        dataType: dataType,
        enableXDR : true,
        reconnect : false,
        context: this.$dialog('Enregistrement').get(0),
        message: function(event, stream) {
            if( typeof( event.data ) == "object" )
            {
                if( typeof(event.data.percent) == "number" && typeof(event.data.message) == "string" )
                {
                    $(this).trigger('update', event.data);
                } else {
                    result = that.execute(event.data, callbackSuccess);
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

EMCXmlHttpRequest.prototype.$dialog = function(title) {
    var dialogClass = 'emc-dialog';
    
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
    
    return $dialog;
};