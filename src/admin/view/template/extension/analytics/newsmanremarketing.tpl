<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-left">
        <!--<button type="submit" form="form-google-analytics" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>-->
      <!--<h1><?php echo $heading_title; ?></h1>-->
      <h1>Newsman Remarketing</h1>
      <!--<ul class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
        <?php } ?>
      </ul>-->
    </div>
  </div>
  <div class="container">
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
        <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-analytics" class="form-horizontal">
          <div class="alert alert-info"><i class="fa fa-info-circle"></i> <?php echo $text_signup; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-code"><?php echo $entry_tracking; ?></label>
            <div class="col-sm-10">
              <input name="analytics_newsmanremarketing_trackingid" placeholder="<?php echo $entry_tracking; ?>" id="input-code" class="form-control" value="<?php echo $analytics_newsmanremarketing_trackingid; ?>">
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-status"><?php $entry_status; ?></label>
            <div class="col-sm-10">
              <select name="newsmanremarketing_status" id="input-status" class="form-control">
                <?php if ($newsmanremarketing_status) { ?>
                <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                <option value="0"><?php echo $text_disabled; ?></option>
                <?php } else { ?>
                <option value="1"><?php echo $text_enabled; ?></option>
                <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                <?php } ?>
              </select>
            </div>
          </div>
          <div class="col-sm-10">
            <input type="submit" name="newsmanRemarketingSubmit" value="Save" class="btn btn-primary">
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php echo $footer; ?>
