function loadList(id = 1) {
  var dataToSend = {
    'action': 'getList',
    'id': id
  };
  // get items to the list
  $.post('index.php', dataToSend, function(data) {
    if(data) {
      $('#list').empty();
      $.each(data, function(key, item) {
        var template = $('#template').html();
        Mustache.parse(template);
        var rendered = Mustache.render(template, {itemid: item.itemid, title: item.title, description: item.description, qty: item.qty, position: item.position});
        $('#list').append(rendered);
      });
      $('#list').listview('refresh');
    }
  }, 'json');
}

var sort = false;

$('document').ready(function() {

  var webAuth = new auth0.WebAuth({
    domain: AUTH0_DOMAIN,
    clientID: AUTH0_CLIENT_ID,
    redirectUri: AUTH0_CALLBACK_URL,
    audience: 'https://' + AUTH0_DOMAIN + '/userinfo',
    responseType: 'token id_token',
    scope: 'openid'
  });

  // buttons and event listeners
  var loginBtn = $('#btn-login');
  var logoutBtn = $('#btn-logout');
  var newItem = $('#addNewItem');
  var refresh = $('#refresh');
  var clear = $('#clearList');
  var new_list = $('#addNewList');
  var navigation = $('.navigation')

  loginBtn.click(function(e) {
    e.preventDefault();
    webAuth.authorize();
  });

  logoutBtn.click(logout);

  function setSession(authResult) {
    // Set the time that the access token will expire at
    var expiresAt = JSON.stringify(
      authResult.expiresIn * 1000 + new Date().getTime()
    );
    localStorage.setItem('access_token', authResult.accessToken);
    localStorage.setItem('id_token', authResult.idToken);
    localStorage.setItem('expires_at', expiresAt);
  }

  function logout() {
    // Remove tokens and expiry time from localStorage
    localStorage.removeItem('access_token');
    localStorage.removeItem('id_token');
    localStorage.removeItem('expires_at');

    displayButtons();
  }

  function isAuthenticated() {
    // Check whether the current time is past the
    // access token's expiry time
    var expiresAt = JSON.parse(localStorage.getItem('expires_at'));
    return new Date().getTime() < expiresAt;
  }

  function handleAuthentication() {
    webAuth.parseHash(function(err, authResult) {
      if (authResult && authResult.accessToken && authResult.idToken) {
        window.location.hash = '';
        setSession(authResult);
        loginBtn.show();
      } else if (err) {
        console.log(err);
        alert(
          'Error: ' + err.error + '. Check the console for further details.'
        );
      }
      displayButtons();
    });
  }

  function displayButtons() {
    if (isAuthenticated()) {
      loginBtn.hide();
      logoutBtn.show();
      newItem.show();
      refresh.show();
      clear.show();
      new_list.show();
      navigation.show();

      if (!localStorage.getItem("reload")) {
        localStorage.setItem("reload", "true");
        location.reload();
      } else {
        localStorage.removeItem("reload");
      }

    } else {
      loginBtn.show();
      logoutBtn.hide();
      newItem.hide();
      refresh.hide();
      clear.hide();
      new_list.hide();
      navigation.hide();

      $('#shoppinglist-page').attr('data-auth', 0);
      $('#list').empty();
    }
  }

  handleAuthentication();

});

// Shoppinglist actions
$( document ).on( "pagecreate", "#shoppinglist-page", function() {

  function isAuthenticated() {
    // Check whether the current time is past the
    // access token's expiry time
    var expiresAt = JSON.parse(localStorage.getItem('expires_at'));
    return new Date().getTime() < expiresAt;
  }

  if(!isAuthenticated()) return false;

  loadList();

  // empty the pop up form
  $('#addNewItem').on('click', function() {
    $('#popUpTitle').html('Uusi artikkeli');
    $('#addItemBtn').html("Lisää");
    $('#item_name').val('');
    $('#item_desc').val('');
    $('#item_qty').val(1);
    $('#id').val('');
  });

  // add new item
  $('#addItemBtn').on('click', function(e) {
    e.preventDefault();
    var dataToSend = {
      action: 'new',
      item: $('#addItemForm').serialize(),
      list_id: $('#shopLists').val(),
      position: $("#list").children().length
    }
    $.post('index.php', dataToSend, function(data) {
      if(data.title) {
        if(data.id == 0) {
          var template = $('#template').html();
          Mustache.parse(template);
          var rendered = Mustache.render(template, {itemid: data.itemid, title: data.title, description: data.description, qty: data.qty, position: data.position});
          $('#list').append(rendered);
          $('#list').listview('refresh');
        } else {
          var id = $('#shopLists').val();
          loadList(id);
        }
        $("#addItem").popup("close");
      }
    },'json');
  });

  // empty the new list pop up form
  $('#addNewList').on('click', function() {
    $('#popUpTitle').html('Uusi Lista');
    $('#addItemBtn').html("Lisää");
    $('#list_name').val('');
  });

  // add new list
  $('#addListBtn').on('click', function(e) {
    e.preventDefault();
    var dataToSend = {
      action: 'new_list',
      list: $('#addListForm').serialize()
    }
    $.post('index.php', dataToSend, function(data) {
      if(data.title) {
        var list_template = $('#list-template').html();
        Mustache.parse(list_template);
        var rendered_option = Mustache.render(list_template, {id: data.id, title: data.title});
        $('#shopLists').append(rendered_option);
        $('#shopLists').selectmenu('refresh', true);
        $("#addList").popup("close");
      }
    },'json');
  });

  // update item
  $('body').on('taphold', '.shoppinglistItem', function() {
    if(sort == false) {
      var id = $(this).attr('data-id');
      var title = $(this).children('a').children('h3').html();
      var description = $(this).children('a').children('p').html();
      var qty = $(this).children('a').children('p.ui-li-aside').children('strong').html();
      $('#item_name').val(title);
      $('#item_desc').val(description);
      $('#item_qty').val(qty);
      $('#id').val(id);
      $('#popUpTitle').html('Muokkaa artikkelia');
      $('#addItemBtn').html("Muokkaa");
      $('#addItem').popup("open");
    }
  });

  // clear list
  $('#clearList').on('click', function(e) {
    e.preventDefault();
    // are you sure?
    $( "#confirm-new" ).popup( "open" );
    $( "#confirm-new #yes" ).on( "click", function() {
      $('#clearList').removeClass( "ui-btn-active" );
      var dataToSend = {
        action: 'clear',
        list_id: $('#shopLists').val()
      };
      $.post('index.php', dataToSend, function(data) {
        if(data.result) {
          $('#list').empty();
        }
      },'json');
    });
    $( "#confirm-new #cancel" ).on( "click", function() {
      $('#clearList').removeClass( "ui-btn-active" );
      $( "#confirm-new #yes" ).off();
    });
  });

  // Swipe to remove list item
  $( document ).on( "swipeleft swiperight", "#list li", function( event ) {
    var listitem = $( this ),
      // These are the classnames used for the CSS transition
      dir = event.type === "swipeleft" ? "left" : "right",
      // Check if the browser supports the transform (3D) CSS transition
      transition = $.support.cssTransform3d ? dir : false;

      confirmAndDelete( listitem, transition );
  });

  // refresh list
  $('#refresh').on('click', function(e) {
    e.preventDefault();
    var id = $('#shopLists').val();
    loadList(id);
  });

  // change list
  $('#shopLists').on('change', function(e) {
    var id = $(this).val();
    loadList(id);
  });

  // delete list
  $('#deleteList').on('click', function(e) {
    e.preventDefault();
    if($('#shopLists').val() == 1) {
      $( "#dialog" ).popup( "open" );
      $( "#dialog #cancel" ).on( "click", function() {
        $('#deleteList').removeClass( "ui-btn-active" );
      });
    } else {
      // are you sure?
      $( "#confirm-delete" ).popup( "open" );
      $( "#confirm-delete #yes" ).on( "click", function() {
        $('#deleteList').removeClass( "ui-btn-active" );
        var dataToSend = {
          action: 'delete_list',
          list_id: $('#shopLists').val()
        };
        $.post('index.php', dataToSend, function(data) {
          if(data.result) {
            $('#list-' + data.id).remove();
            $('#shopLists').selectmenu('refresh', true);
            var id = $('#shopLists').val();
            loadList(id);
          }
        },'json');
      });
      $( "#confirm-delete #cancel" ).on( "click", function() {
        $('#deleteList').removeClass( "ui-btn-active" );
        $( "#confirm-delete #yes" ).off();
      });
    }
  });

  // update list order
  $('#sortList').on('click', function(e) {
    if(sort == false) {
      sort = true;
      $('#sortList').html( "Tallenna");
      $("#list").sortable();
      $("#list").bind( "sortstop", function(event, ui) {
         var items = [];
         $("#list li").each(function(index, item) {
           var item = {
             id: $(this).attr('data-id'),
             position: $(this).attr('data-position')
           };
           items.push(item);
         });
         var dataToSend = {
           action: 'update_position',
           data: items
         };
         $.post('index.php', dataToSend, function(data) {
           if(data.result) {
             $('#list').listview('refresh');
           }
         },'json');
       });
     } else {
       $.mobile.activePage.focus();
       $('#sortList').html( "Muuta järjestystä");
       $("#list").sortable("disable");
       sort = false;
     }
  });
  
  // import list items
  $('#importListBtn').on('click', function(e) {
    e.preventDefault();
    var dataToSend = {
      action: 'import_list',
      items: $('#importListForm').serialize(),
      list_id: $('#shopLists').val(),
      position: $("#list").children().length
    }
    $.post('index.php', dataToSend, function(data) {
      if(data) {
        $.each(data, function(key, item) {
          var template = $('#template').html();
          Mustache.parse(template);
          var rendered = Mustache.render(template, {itemid: item.itemid, title: item.title, description: item.description, qty: item.qty, position: item.position});
          $('#list').append(rendered);
        });
        $('#list').listview('refresh');
        $("#importList").popup("close");
      }
    },'json');
  });

  // enable multiple actions on one button
  $('#sortList').on('mouseout', function(e) {
    $('#sortList').removeClass("ui-btn-active");
    $.mobile.activePage.focus();
  });

  // If it's not a touch device...
  if ( ! $.mobile.support.touch ) {

    // Remove the class that is used to hide the delete button on touch devices
    $( "#list" ).removeClass( "touch" );

    // Click delete split-button to remove list item
    $( ".delete" ).on( "click", function() {
      var listitem = $( this ).parent( "li" );

      confirmAndDelete( listitem );
    });
  }

  function confirmAndDelete( listitem, transition ) {
    // Highlight the list item that will be removed
    listitem.children( ".ui-btn" ).addClass( "ui-btn-active" );
    // Inject topic in confirmation popup after removing any previous injected topics
    $( "#confirm .topic" ).remove();
    listitem.find( ".topic" ).clone().insertAfter( "#question" );
    // Show the confirmation popup
    $( "#confirm" ).popup( "open" );
    // Proceed when the user confirms
    $( "#confirm #yes" ).on( "click", function() {

      var dataToSend = {
        action: 'delete',
        id: listitem.attr('data-id'),
        list_id: $('#shopLists').val()
      }

      $.post('index.php', dataToSend, function(data) {
        if(data.result)
        {
          // Remove with a transition
          if ( transition ) {

            listitem
            // Add the class for the transition direction
            .addClass( transition )
            // When the transition is done...
            .on( "webkitTransitionEnd transitionend otransitionend", function() {
              // ...the list item will be removed
              listitem.remove();
              // ...the list will be refreshed and the temporary class for border styling removed
              $( "#list" ).listview( "refresh" ).find( ".border-bottom" ).removeClass( "border-bottom" );
            })
            // During the transition the previous button gets bottom border
            .prev( "li" ).children( "a" ).addClass( "border-bottom" )
            // Remove the highlight
            .end().end().children( ".ui-btn" ).removeClass( "ui-btn-active" );
          }
          // If it's not a touch device or the CSS transition isn't supported just remove the list item and refresh the list
          else {
            listitem.remove();
            $( "#list" ).listview( "refresh" );
          }
        }
      },'json');
    });
    // Remove active state and unbind when the cancel button is clicked
    $( "#confirm #cancel" ).on( "click", function() {
      listitem.children( ".ui-btn" ).removeClass( "ui-btn-active" );
      $( "#confirm #yes" ).off();
    });
  }
});
