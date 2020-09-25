<?php echo $header; ?>
<?php echo $column_left; ?>
<style>
  .optimg-image-size {
    background: green;
    color: white;
    padding: 8px;
    font-size: 16px;
    display: inline-block;
    min-width: 66px
  }
  .optimg-image-url{
    background: grey;
    color: white;
    padding: 8px;
    font-size: 16px;
    display: inline-block
  }
</style>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form-dfdesign-optimg" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
        <h1><?php echo $heading_title ?></h1>

        <ul class="breadcrumb">
          <?php foreach ($breadcrumbs as $breadcrumb) { ?>
          <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
          <?php } ?>
        </ul>

      </div>
    </div>
    <div class="container-fluid">
      <?php if ($error_warning) { ?>
      <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
      </div>
      <?php } ?>
      <div class="panel panel-default">

        <div class="panel-heading">
          <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
        </div>

        <div class="panel-body">

          <div class="row">
            <div class="col-sm-3">
              <h4><span id="number-of-optimised"><?php echo $number_of_optimised;?></span> <?php echo $images_text ?> <strong><?php echo $optimised_text ?></strong></h4>
              <h4><span id="number-of-not-optimised"><?php echo $number_of_not_optimised;?></span> <?php echo $images_text ?> <strong><?php echo $not_optimised_text ?></strong></h4>
              <button type="button" class="btn btn-success btn-lg" id="optimise-btn" data-loading-text="<i class='fa fa-circle-o-notch fa-spin'></i> Optimising ..."><i style="visibility: hidden;margin-right:10px" class='fa fa-circle-o-notch fa-spin' id='load-circle'></i><?php echo $optimise_text ?></button>
            </div>
            <div class="col-sm-3">
              <img src="../../image/dfdesign_optimg/Loader.gif" width="230" height="200" id="load_icon" style="visibility: hidden">
            </div>
          </div>

          <div class="row">
            <div class="col-sm-12">
              <div id="message"></div>
            </div>
          </div>

        </div>

      </div>
    </div>
  </div>
  <link href="../../javascript/bootstrap/js/bootstrap.min.js" rel="stylesheet" />
  <script type="text/javascript"><!--
  
    urli = 'index.php?route=module/dfdesign_optimg&token=<?php echo $token; ?>';
    jQuery(document).ready(function() {

      var el = document.getElementById("optimise-btn");
      
      el.addEventListener("click", function(e){
        event.preventDefault();
        var count = 0;

        function recursiveAjax(){
          document.getElementById("load_icon").style.visibility = "visible";
          document.getElementById("load-circle").style.visibility = "visible";
          jQuery.ajax({
            urli,
            data: {action: 'send_image'},
            type: 'post',
            dataType: 'html',
            async: true,
             success: function(output) {
              var JSONOut = jQuery.parseJSON(output);
              if(JSONOut != null){
                var text= '<table>';
                for(var a = 0; a < JSONOut.length; a++){
                  text += "<span class='optimg-image-size'>" + JSONOut[0].optiSize + " % </span><span class='optimg-image-url'>" + JSONOut[0].url + "</span><br>" ;
                }
                text += '<table>';
                jQuery('#number-of-optimised').html(JSONOut[0].optimisedNum);
                jQuery('#number-of-not-optimised').html(JSONOut[0].notOptimisedNum);
                jQuery('#message').html(text);
              }else{
                jQuery('#message').html('<div class="alert alert-info"><h3>NO FILES</h3></div>');
              }
              count++;
              if(count < 6){
                setTimeout(recursiveAjax, 3000);
              }
            },

          complete: function(){
            document.getElementById("load_icon").style.visibility = "hidden";
            document.getElementById("load-circle").style.visibility = "hidden";
          }
        });

  } // end recursiveAjax
  recursiveAjax();
  
}, false);

    });

//--></script>
<?php echo $footer; ?>