

function load_content(target_elt, url, params) {
    new Ajax.Request(url, {
      method: "GET",
      parameters: params,
      onComplete: function(transport) {
                        target_elt.innerHTML  = transport.responseText;
                  }
    })
}
