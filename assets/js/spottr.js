jQuery(document).ready(function ($) {
  //login
  $("#spottrForm").submit(function (e) {
    e.preventDefault();
    var form = $(this);
    var email = form.find('input[name="email"]').val();
    var password = form.find('input[name="password"]').val();
    //check if email and password are not empty
    if (email == "" && password == "") {
      alert("Please enter email and password");
      return;
    }
    //ajax
    $.ajax({
      type: "POST",
      url: spottr.spottr_ajax_url,
      data: {
        action: "spottr_login",
        email: email,
        password: password,
        spottr_nonce: spottr.spottr_nonce
      },
      dataType: "json",
      beforeSend: function () {
        //sweet alert loading
        Swal.fire({
          title: "Please wait...",
          text: "Authenticating to Spottr",
          allowOutsideClick: false,
          allowEscapeKey: false,
          allowEnterKey: false,
          showConfirmButton: false,
          onOpen: () => {
            Swal.showLoading();
          }
        });
        //disable all form
        form.find("input, button").prop("disabled", true);
        //submit button text
        form.find("input[type=submit]").val("Authenticating...");
      },
      success: function (response) {
        //close sweet alert
        Swal.close();
        //check if code is 200
        if (response.code == 200) {
          //sweet alert
          Swal.fire({
            title: "Success",
            text: response.message,
            icon: "success",
            allowOutsideClick: false,
            allowEscapeKey: false,
            confirmButtonText: "Import categories",
            showCancelButton: true,
            cancelButtonText: "Cancel"
          }).then((result) => {
            if (result.value) {
              //ajax
              $.ajax({
                type: "GET",
                url: spottr.spottr_ajax_url,
                data: {
                  action: "spottr_content"
                },
                beforeSend: function () {
                  //sweet alert loading
                  Swal.fire({
                    title: "Please wait...",
                    text: "Importing categories",
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    allowEnterKey: false,
                    showConfirmButton: false,
                    onOpen: () => {
                      Swal.showLoading();
                    }
                  });
                },
                success: function (response) {
                  //close sweet alert
                  Swal.close();
                  //check response
                  if (response.code == 200) {
                    //sweet alert
                    Swal.fire({
                      title: "Success",
                      text: response.message,
                      icon: "success",
                      confirmButtonText: "Ok"
                    }).then((result) => {
                      if (result.value) {
                        //reload
                        location.reload();
                      }
                    });
                  } else {
                    //show error
                    Swal.fire({
                      title: "Error",
                      text: response.message,
                      icon: "error",
                      confirmButtonText: "Ok"
                    });
                  }
                }
              });
            } else {
              //reload
              location.reload();
            }
          });
        } else {
          //show error
          Swal.fire({
            title: "Error",
            text: response.message,
            icon: "error",
            confirmButtonText: "Ok"
          });
          //enable
          form.find("input, button").prop("disabled", false);
          //submit button text
          form.find("input[type=submit]").val("Authenticate");
        }
      }
    });
  });

  //spottrDisconnect
  $(".spottrDisconnect").click(function (e) {
    e.preventDefault();
    var btn = $(this);
    //ajax
    $.ajax({
      type: "POST",
      url: spottr.spottr_ajax_url,
      data: {
        action: "spottr_disconnect",
        spottr_nonce: spottr.spottr_nonce
      },
      dataType: "json",
      beforeSend: function () {
        //sweet alert loading
        Swal.fire({
          title: "Please wait...",
          text: "Disconnecting from Spottr",
          allowOutsideClick: false,
          allowEscapeKey: false,
          allowEnterKey: false,
          showConfirmButton: false,
          onOpen: () => {
            Swal.showLoading();
          }
        });
        //disable all form
        btn.prop("disabled", true);
        //submit button text
        btn.text("Disconnecting...");
      },
      success: function (response) {
        //close sweet alert
        Swal.close();
        //check if code is 200
        if (response.code == 200) {
          //sweet alert
          Swal.fire({
            title: "Success",
            text: "You have been disconnected from Spottr",
            icon: "success",
            confirmButtonText: "Ok"
          }).then((result) => {
            if (result.value) {
              //reload
              location.reload();
            }
          });
        } else {
          //show error
          Swal.fire({
            title: "Error",
            text: response.message,
            icon: "error",
            confirmButtonText: "Ok"
          });
          //enable
          btn.prop("disabled", false);
          //submit button text
          btn.text("Disconnect");
        }
      }
    });
  });

  //spottr_content
  $(".spottr_content").click(function (e) {
    e.preventDefault();
    var btn = $(this);
    //ajax
    $.ajax({
      type: "GET",
      url: spottr.spottr_ajax_url,
      data: {
        action: "spottr_content"
      },
      beforeSend: function () {
        //sweet alert loading
        Swal.fire({
          title: "Please wait...",
          text: "Importing categories",
          allowOutsideClick: false,
          allowEscapeKey: false,
          allowEnterKey: false,
          showConfirmButton: false,
          onOpen: () => {
            Swal.showLoading();
          }
        });
      },
      success: function (response) {
        //close sweet alert
        Swal.close();
        //check response
        if (response.code == 200) {
          //sweet alert
          Swal.fire({
            title: "Success",
            text: response.message,
            icon: "success",
            confirmButtonText: "Ok"
          }).then((result) => {
            if (result.value) {
              //reload
              location.reload();
            }
          });
        } else {
          //show error
          Swal.fire({
            title: "Error",
            text: response.message,
            icon: "error",
            confirmButtonText: "Ok"
          });
        }
      }
    });
  });
});

//syncSpottr
let syncSpottr = (element, e) => {
  //prevent default
  e.preventDefault();
  jQuery(document).ready(function ($) {
    let button = $(element);
    //sweet alert confirm
    Swal.fire({
      title: "Are you sure?",
      text: "This will sync current product with Spottr",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Sync",
      cancelButtonText: "Cancel"
    }).then((result) => {
      if (result.value) {
        var lat = sessionStorage.getItem("lat");
        var lng = sessionStorage.getItem("lng");
        //if lat and lng is not set
        if (lat == null || lng == null) {
          //show error
          Swal.fire({
            title: "Error",
            text: "Please select a location first",
            icon: "error",
            confirmButtonText: "Ok"
          });
          return;
        }
        //get product id
        var product_id = button.data("id");
        //ajax
        $.ajax({
          type: "POST",
          url: spottr.spottr_ajax_url,
          data: {
            action: "sync_spottr",
            spottr_nonce: spottr.spottr_nonce,
            lat: lat,
            lng: lng,
            product_id: product_id
          },
          dataType: "json",
          beforeSend: function () {
            //sweet alert loading
            Swal.fire({
              title: "Please wait...",
              text: "Syncing product with Spottr",
              allowOutsideClick: false,
              allowEscapeKey: false,
              allowEnterKey: false,
              showConfirmButton: false,
              onOpen: () => {
                Swal.showLoading();
              }
            });
            //disable all form
            button.prop("disabled", true);
            //submit button text
            button.text("Syncing...");
          },
          success: function (response) {
            //close sweet alert
            Swal.close();
            //check if code is 200
            if (response.code == 200) {
              //sweet alert
              Swal.fire({
                title: "Success",
                text: response.message,
                icon: "success",
                confirmButtonText: "Ok"
              }).then((result) => {
                if (result.value) {
                  //update button text
                  //get closest td
                  let td = button.closest("td");
                  //html
                  let html = `
                <span class="dashicons dashicons-yes" style="color: green;"></span> Synced
                `;
                  //update html
                  td.html(html);
                }
              });
            } else {
              //show error
              Swal.fire({
                title: "Error",
                text: response.message,
                icon: "error",
                confirmButtonText: "Ok"
              });
              //enable
              button.prop("disabled", false);
              //submit button text
              button.text("Sync");
            }
          }
        });
      }
    });
  });
};

function initGeolocation() {
  if (navigator.geolocation) {
    // Call getCurrentPosition with success and failure callbacks
    navigator.geolocation.getCurrentPosition(success, fail);
  } else {
    alert("Sorry, your browser does not support geolocation services.");
  }
}

function success(position) {
  var lng = position.coords.longitude;
  var lat = position.coords.latitude;

  //update input
  jQuery('input[name="lat"]').val(lat);
  jQuery('input[name="lng"]').val(lng);
  //session storage
  sessionStorage.setItem("lat", lat);
  sessionStorage.setItem("lng", lng);
}

function fail() {
  // Could not obtain location
  console.log("fail");
}

jQuery(document).ready(function ($) {
  initGeolocation();
});
