{if $errorMessage != ""}
<div class="alert alert-warning" role="alert">
    {$errorMessage}
  </div>
{else}


<div class="modal fade" id="confirmModal" tabindex="-1" role="dialog" data-backdrop="false" aria-labelledby="modalLabelLarge" aria-hidden="true">
   <div class="modal-dialog" style="max-width:500px;">
      <div class="modal-content">
         <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title" id="modalLabelLarge">Confirm Action</h4>
         </div>
         <div class="modal-body">
            <div class="form-group">
<span id="actionMessage"></span>
            </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary bbtn" data-dismiss="modal">Close</button>
            <button  id="confirm" type="button" class="btn btn-success ">Continue</button>
         </div>
      </div>
   </div>
</div>


<div class="panel panel-primary">
  <div class="panel-heading">Application URL</div>
  <div class="panel-body">
  <a href="{$appUrl}/?instance={$instanceId}" target="_blank">{$appUrl}/?instance={$instanceId}</a>
  </div>
    </div>

{if !empty($endpoints) }
<div class="panel panel-primary">
  <div class="panel-heading">Endpoints</div>
  <div class="panel-body" >

  {foreach $endpoints as $e}
  <h4>{$e['DisplayName']}</h4>{if !empty($e['Uri'])}
  <a target="_blank" href="{$e['Uri']}" style="vertical-align: middle;" target="_blank">{$e['Endpoint']}</a>
  {else}{$e['Endpoint']}{/if}

  <button style="margin-left: 16px;" onclick="copyToClipboard('{$e['Endpoint']}')" class="btn btn-default" >Copy to clipboard</button>

 <hr>

  {/foreach}
</div>
  
    </div>
{/if}
    <div class="panel panel-default">
  <div class="panel-heading">Application Management</div>
  <div class="panel-body">

<h2>Status: <span id="status"></span></h2>
<button id="start" class="btn btn-default" data-toggle="modal" data-target="#confirmModal">Start Instance</button>
<button id="stop" class="btn btn-default" data-toggle="modal" data-target="#confirmModal">Stop Instance</button>
<button id="restart" class="btn btn-default" data-toggle="modal" data-target="#confirmModal">Restart Instance</button>
<button id="resetPassword" class="btn btn-default" data-toggle="modal" data-target="#confirmModal">Reset Password</button>

  </div>
    </div>
{/if}


<div id="custom-alert" style="display:none;position:fixed; 
top:0px; 
right: 0px; 
max-width: 800px;
z-index:9999; 
border-radius:0px" class="alert alert-danger collapse alertUpCloud">
    <button type="button" id="close-custom-alert" class="close" style="margin-left:5px;" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
    <span id="custom-alert-message"></span>

</div>
<div id="custom-loader" class="loader" style='display:none; margin:-20px;'></div>


<style>

.loader {
    position:fixed; 
    z-index:9999; 
top:40px; 
right: 50px; 
  display: inline-block;
  width: 50px;
  height: 50px;
  border: 3px solid rgba(255,255,255,.3);
  border-radius: 50%;
  border-top-color: rgb(0, 0, 0);
  animation: spin 1s ease-in-out infinite;
  -webkit-animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
  to { -webkit-transform: rotate(360deg); }
}
@-webkit-keyframes spin {
  to { -webkit-transform: rotate(360deg); }
}

.alert-fixed {
    position:fixed; 
    top: 0px; 
    left: 0px; 
    width: 100%;
    z-index:9999; 
    border-radius:0px
}
</style>
<script>

var serviceid = "{$moduleParams['serviceid']}";
var ajaxUrl = "clientarea.php?action=productdetails&id=" + serviceid;
var status = "{$state}";
var act = '';

$( "#status" ).html(status);
$( "#status" ).css('color', 'red');
if(status == 'Running')
{
  $( "#status" ).css('color', 'green');
}

$( document ).ready(function() {
  $( document ).ajaxSend(function() {
      $('.modal').modal('hide');
      $('#mg-wrapper .btn').prop('disabled',true);
      $("#custom-loader").show();
  });

  $( document ).ajaxComplete(function() {
      $('#mg-wrapper .btn').prop('disabled',false);
      $("#custom-loader").hide();
  });

  $( document ).on( "click", ".btn", function() {
      $( "#custom-alert" ).hide();
  });

  $( "#close-custom-alert" ).click(function() {
      $( "#custom-alert" ).hide();
  });

  onButton('start','startInstance');
   onButton('stop','stopInstance');
    onButton('restart','restartInstance');
     onButton('resetPassword','resetPassword');
});





function parseResponse(data)
{
  if(data)
  {
  try 
  {
    var parsed = jQuery.parseJSON(data);
    if(parsed.result == 'success')
    {

      $( "#status" ).html(status);
      $( "#status" ).css('color', 'red');
      if(status == 'Active')
      {
        $( "#status" ).css('color', 'green');
      }

      if(parsed.message != '')
      {
        $('#custom-alert-message').html(parsed.message);
      }
      else
      {
        $('#custom-alert-message').html('Action completed successfully');
      }
      $('#custom-alert').removeClass().addClass('alert alert-success collapse alertUpCloud').show();
      return true;
    }
    else
    {
      if(parsed.message != '')
      {
        $('#custom-alert-message').html(parsed.message);
      }
      else
      {
        $('#custom-alert-message').html('Can not complete action');
      }
      $('#custom-alert').removeClass().addClass('alert alert-danger collapse alertUpCloud').show();
    }
    return false;
  } catch(e) {
      console.log(e);
      $('#custom-alert-message').html('Can not parse message');
      $('#custom-alert').removeClass().addClass('alert alert-danger collapse alertUpCloud').show();
      return false;
  }
 }
}


function onButton(id, action)
{
    $(document).on('click', '#'+id, function() {
      act = action;

      if(act == 'startInstance')
      {
        message = 'You are about to start instance. Do you want to proceed?';
      }else if(act == 'stopInstance')
      {
         message = 'You are about to stop instance. Do you want to proceed?';
      }else if(act == 'restartInstance')
      {
         message = 'You are about to restart instance. Do you want to proceed?';
      }else if(act == 'resetPassword')
      {
         message = 'You are about to reset password. You will receive new one via email. Do you want to proceed?';
      }
      $( "#actionMessage" ).html(message);
 });
}

  $(document).on('click', '#confirm', function() {
    $.post(ajaxUrl + "&subaction=" + act)
        .done(function(data) {
            res = parseResponse(data);
            if(res)
            {
              if(act == 'startInstance' || act == 'restartInstance')
              {
                $( "#status" ).html('Running').css('color', 'green');
              }
              else if(act == 'stopInstance')
              {
                $( "#status" ).html('Stopped').css('color', 'red');
              }
            }
        });
 });

function copyToClipboard(value) {
  var tempInput = document.createElement("input");
  tempInput.value = value;
  document.body.appendChild(tempInput);
  tempInput.select();
  document.execCommand("copy");
  document.body.removeChild(tempInput);
}

</script>
