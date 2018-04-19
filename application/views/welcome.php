<?php
// vim:set sw=2 tabstop=2 et nowrap:
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Supervisord Monitoring</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link type="text/css" rel="stylesheet" href="<?=base_url('/css/bootstrap.min.css')?>"/>
  <link type="text/css" rel="stylesheet" href="<?=base_url('/css/bootstrap-responsive.min.css')?>"/>
  <link type="text/css" rel="stylesheet" href="<?=base_url('/css/custom.css')?>"/>
  <script type="text/javascript" src="<?=base_url('/js/jquery-1.10.1.min.js')?>"></script>
  <script type="text/javascript" src="<?=base_url('/js/bootstrap.min.js')?>"></script>
  <noscript>
  <?php if($this->config->item('refresh')) { ?>
  <meta http-equiv="refresh" content="<?=$this->config->item('refresh')?>">
  <?php } ?>
  </noscript>
</head>
<body>
  <div class="navbar navbar-inverse navbar-fixed-top">
    <div class="navbar-inner">
      <div class="container">
        <button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse" >
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </button>
        <a class="brand" href="<?=site_url('')?>">Support Center</a>
        <div class="nav-collapse collapse">
          <ul class="nav">
            <li class="active"><a href="<?=site_url()?>">Home</a></li>
            <li><a href="?mute=<?=($muted?-1:1)?>"><i class="icon-music icon-white"></i>&nbsp;<?=($muted?"Unmute":"Mute")?></a></li>
            <li><a href="<?=site_url()?>">Refresh <b id="refresh">(<?=$this->config->item('refresh')?>)</b> &nbsp;</a></li>
            <li><a href="mailto:martin@lazarov.bg">Contact</a></li>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </div>
  </div>

  <div class="container">
    <?php if($muted) { ?>
    <div class="row">
      <div class="span4 offset4 label label-important" style="padding:10px;margin-bottom:20px;text-align:center;">
        Sound muted for <?=timespan(time(),$muted)?>
        <span class="pull-right"><a href="?mute=-1" style="color:white;"><i class="icon-music icon-white"></i> Unmute</a></span>
      </div>
    </div>
    <?php } ?>
    <div class="row">
      <?php
      $alert = false;
      foreach($list as $name=>$procs) {
        $parsed_url = parse_url($cfg[$name]['url']);
        if (isset($cfg[$name]['username']) && isset($cfg[$name]['password'])) $auth = $cfg[$name]['username'] . ':' . $cfg[$name]['password'] . '@';
        $ui_url = 'http://' . $auth . $parsed_url['host'] . ':' . $cfg[$name]['port']. '/';
      ?>
      <div class="span<?=($this->config->item('supervisor_cols')==2?'6':'4')?>">
        <table class="table table-bordered table-condensed table-striped">
          <tr>
            <th colspan="4">
              <a href="<?=$ui_url?>"><?=$name?></a>
              <?php if($this->config->item('show_host')) { ?>
              <i><?=$parsed_url['host']?></i>
              <?php } ?>
              <?php if(isset($cfg[$name]['username'])) { ?>
              <i class="icon-lock icon-green" style="color:blue" title="Authenticated server connection"></i>
              <?php } ?>
              &nbsp;<i><?=$version[$name] ?></i>
              <?php if(!isset($procs['error'])) { ?>
              <span class="server-btns pull-right">
                <a href="<?=site_url('/control/stopall/'.$name)?>" class="btn btn-mini btn-inverse" type="button"><i class="icon-stop icon-white"></i> Stop all</a>
                <a href="<?=site_url('/control/startall/'.$name)?>" class="btn btn-mini btn-success" type="button"><i class="icon-play icon-white"></i> Start all</a>
                <a href="<?=site_url('/control/restartall/'.$name)?>" class="btn btn-mini btn-primary" type="button"><i class="icon icon-refresh icon-white"></i> Restart all</a>
                <a href="<?=site_url('/control/clearall/'.$name)?>" class="btn btn-mini btn-danger" type="button"><i class="icon icon-stop icon-white"></i>Clear all</a>
              </span>
              <?php } ?>
            </th>
          </tr>
          <?php
          $CI = &get_instance();
          foreach($procs as $item) {
            $item_name = ($item['group'] != $item['name']) ? $item['group'].":".$item['name'] : $item['name'];
            $check = $CI->_request($name,'readProcessStderrLog',array($item_name,0,0));
            if(is_array($check)) $check = print_r($check,1);

            if(!is_array($item)) {
                echo '<tr><td colspan="4">'.$item.'</td></tr>';
                echo '<tr><td colspan="4">For Troubleshooting <a href="https://github.com/mlazarov/supervisord-monitor#troubleshooting" target="_blank">check this guide</a></td></tr>';
                continue;
            }

            $pid = $uptime = '&nbsp;';
            $status = $item['statename'];
            if($status=='RUNNING') { $class = 'success'; list($pid,$uptime) = explode(",",$item['description']); }
            elseif($status=='STARTING') $class = 'info';
            elseif($status=='FATAL') { $class = 'important'; $alert = true; }
            elseif($status=='STOPPED') $class = 'inverse';
            else $class = 'error';

            $uptime = str_replace("uptime ","",$uptime);
          ?>
          <tr>
            <td>
              <?php
              echo $item_name;
              if($check) { 
                $alert = true; 
              ?>
              <span class="pull-right">
              <a href="<?=site_url('/control/clear/'.$name.'/'.$item_name)?>"
                id="<?=$name?>_<?=$item_name?>"
                onclick="return false"
                data-toggle="popover"
                data-placement="bottom"
                data-html="true"
                data-message="<?=htmlspecialchars($check)?>"
                data-original-title="<?=$item_name?>@<?=$name?>"
                class="pop btn btn-mini btn-danger">
                  <img src="<?=base_url('/img/alert_icon.png')?>" />
              </a>
              </span>
              <?php } ?>
            </td>
            <td width="10"><span class="label label-<?=$class?>"><?=$status?></span></td>
            <td width="80" style="text-align:right"><?=$uptime?></td>
            <td style="width:1%">
              <div class="actions" style="width:60px">
                <?php if($status=='RUNNING') { ?>
                <a href="<?=site_url('/control/stop/'.$name.'/'.$item_name)?>" class="btn btn-mini btn-inverse" type="button"><i class="icon-stop icon-white"></i></a>
                <a href="<?=site_url('/control/restart/'.$name.'/'.$item_name)?>" class="btn btn-mini btn-inverse" type="button"><i class="icon-refresh icon-white"></i></a>
                <?php } if($status=='STOPPED' || $status == 'EXITED' || $status=='FATAL') { ?>
                <a href="<?=site_url('/control/start/'.$name.'/'.$item_name)?>" class="btn btn-mini btn-success" type="button"><i class="icon-play icon-white"></i></a>
                <?php } ?>
              </div>
            </td>
          </tr>
          <?php } ?>
        </table>        
      </div>
      <?php } ?>
      <?php if($alert && !$muted && $this->config->item('enable_alarm')) { ?>
      <embed height="0" width="0" src="<?=base_url('/sounds/alert.mp3')?>">
      <?php } ?>
      <?php if($alert) { ?>
      <title>!!! WARNING !!!</title>
      <?php } else { ?>
      <title>Support center</title>
      <?php } ?>
    </div>
  </div> <!-- /container -->
  
  <div class="footer">
    <p>Powered by <a href="https://github.com/mlazarov/supervisord-monitor" target="_blank">Supervisord Monitor</a> | Page rendered in <strong>{elapsed_time}</strong> seconds</p>
  </div>
  <script>
  function show_content($param) {
    stopTimer();
    $time = new Date();
    $message = $(this).data('message');
    $title = $(this).data('original-title');
    $content = `
      <div class="clearfix">
          <div class="pull-left">
              <form method="get" action="<?=$this->config->item('redmine_url')?>" style="display:inline" target="_blank">
                  <input type="hidden" name="issue[subject]" value="`+$title+`"/>
                  <input type="hidden" name="issue[description]" value="`+encodeURIComponent($message)+`"/>
                  <input type="hidden" name="issue[assigned_to_id]" value="<?=$this->config->item('redmine_assigne_id')?>"/>
                  <input type="submit" class="btn btn-small btn-inverse" value="Start New Ticket"/>
              </form>
          </div>
          <div class="pull-right">
              <a href="#" onclick="$(\'#`+$(this).attr('id')+`\').popover(\'hide\');startTimer();" class="btn btn-small btn-primary">ok</a>&nbsp;&nbsp;
              <a href="`+$(this).attr('href')+`" class="btn btn-small btn-danger">Clear</a> &nbsp;
          </div>
      </div>
      <div class="well" style="padding:20px;"><pre>`+$message+`</pre></div>`;
    return $content;
  }
  $('.pop').popover( {content: show_content});
  var $refresh = <?=$this->config->item('refresh')?>;
  var $timer = false;
  $(window).load( function() {
      if($refresh > 0) {
        startTimer();
      }
  });
  function stopTimer() {
    $('#refresh').html('(p)');
    clearInterval($timer);
  }
  function startTimer() {
    if($timer)  stopTimer();
    $timer = setInterval(timer,999);
  }
  function timer() {
    $refresh--;
    $('#refresh').html('('+$refresh+')');
    if($refresh<=0) {
      stopTimer();
      location.href="<?=site_url() ?>";
    }
  }
  function nl2br (str, is_xhtml) {
    var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br ' + '/>' : '<br>';
    return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
  }
  </script>
</body>
</html>
