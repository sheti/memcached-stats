<?php
// don't cache this page
//
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");                                    // HTTP/1.0

define('DATE_FORMAT','d.m.Y G:i:s');
define('HOST', 'unix:///var/run/memcached/memcached.sock');
define('PORT', 0);

try {
    $memcache = new Memcache;
    if(!$memcache->connect(HOST, PORT))
        $memcache = null;  
} catch (Exception $e) {
    $memcache = null;
}

if($memcache !== null) {
    if(array_key_exists('act', $_GET) && $_GET['act'] == 'flush')
           $memcache->flush(); 
    $memcache_stats = $memcache->getStats();
    //print_r($memcache_stats);

    $hits = $memcache_stats['get_hits'];
    $misses = $memcache_stats['get_misses'];
    $total_hits_and_misses = $hits + $misses;
    if($total_hits_and_misses == 0) {
        $percent_hits = 0;
        $percent_misses = 0;
    } else {
        $percent_hits = round(($hits * 100)/$total_hits_and_misses, 2);
        $percent_misses = round(($misses * 100)/$total_hits_and_misses, 2);
    }
}
$time = time();
function duration($ts) {
    global $time;
    $years = (int)((($time - $ts)/(7*86400))/52.177457);
    $rem = (int)(($time-$ts)-($years * 52.177457 * 7 * 86400));
    $weeks = (int)(($rem)/(7*86400));
    $days = (int)(($rem)/86400) - $weeks*7;
    $hours = (int)(($rem)/3600) - $days*24 - $weeks*7*24;
    $mins = (int)(($rem)/60) - $hours*60 - $days*24*60 - $weeks*7*24*60;
    $str = '';
    if($years==1) $str .= "$years year, ";
    if($years>1) $str .= "$years years, ";
    if($weeks==1) $str .= "$weeks week, ";
    if($weeks>1) $str .= "$weeks weeks, ";
    if($days==1) $str .= "$days day,";
    if($days>1) $str .= "$days days,";
    if($hours == 1) $str .= " $hours hour and";
    if($hours>1) $str .= " $hours hours and";
    if($mins == 1) $str .= " 1 minute";
    else $str .= " $mins minutes";
    return $str;
}
function bsize($s) {
	foreach (array('','K','M','G') as $i => $k) {
		if ($s < 1024) break;
		$s/=1024;
	}
	return sprintf("%5.1f %sBytes",$s,$k);
}
?>
<!DOCTYPE html>
<html>
    <head>
        <title>MEMCACHE Stat</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <!-- Bootstrap -->
        <link href="css/bootstrap.min.css" rel="stylesheet" media="screen">
        <style>
            .container {
                max-width: 80%;
                padding-top: 1em;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <?php if($memcache !== null): ?>
            <div class="navbar">
                <div class="navbar-inner">
                    <span class="brand">MEMCACHE Stat</span>
                    <ul class="nav">
                        <li class="active"><a href="?op=1">View Host Stats</a></li>
                        <li><a href="?op=2">Variables</a></li>
                    </ul>
                </div>
            </div>
            <div class="row">
                <div class="span4">
                    <h5>General Cache Information</h5>
                    <table class="table table-condensed table-hover">
                        <tr>
                            <td>PHP Version</td>
                            <td><?php echo phpversion(); ?></td>
                        </tr>
                        <tr>
                            <td>Memcached Host</td>
                            <td><?php echo HOST, ':', PORT; ?></td>
                        </tr>
                    </table>
                    <h5>Memcache Server Information</h5>
                    <table class="table table-condensed table-hover">
                        <tr>
                            <td>Memcached Server Version</td>
                            <td><?php echo $memcache->getVersion(); ?></td>
                        </tr>
                        <tr>
                            <td><a href="?op=1&act=flush">Flush Server</a></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Start Time</td>
                            <td style="font-size: 80%;"><?php echo date(DATE_FORMAT, $memcache_stats['time'] - $memcache_stats['uptime']); ?></td>
                        </tr>
                        <tr>
                            <td>Uptime</td>
                            <td style="font-size: 80%;"><?php echo duration($memcache_stats['time'] - $memcache_stats['uptime']); ?></td>
                        </tr>
                        <tr>
                            <td>Used Cache Size</td>
                            <td><?php echo bsize($memcache_stats['bytes']); ?></td>
                        </tr>
                        <tr>
                            <td>Total Cache Size</td>
                            <td><?php echo bsize($memcache_stats['limit_maxbytes']); ?></td>
                        </tr>
                        <tr>
                            <td>Bytes writen</td>
                            <td><?php echo bsize($memcache_stats['bytes_written']); ?></td>
                        </tr>
                        <tr>
                            <td>Bytes read</td>
                            <td><?php echo bsize($memcache_stats['bytes_read']); ?></td>
                        </tr>
                        <tr>
                            <td>Current Items Count</td>
                            <td><?php echo $memcache_stats['curr_items']; ?></td>
                        </tr>
                        <tr>
                            <td>Items Count</td>
                            <td><?php echo $memcache_stats['total_items']; ?></td>
                        </tr>
                    </table>
                </div>
                <div class="span8">
                    <div class="row-fluid">
                        <div class="span6">
                            <div id="memstat" style="width:300px; height:300px"></div>
                        </div>
                        <div class="span6">
                            <div id="hmstat" style="width:300px; height:300px"></div>
                        </div>
                    </div>
                    <h5>Cache Information</h5>
                    <table class="table table-condensed table-hover">
                        <tr>
                            <td>Hits</td>
                            <td><?php echo $hits; ?></td>
                        </tr>
                        <tr>
                            <td>Misses</td>
                            <td><?php echo $misses; ?></td>
                        </tr>
                        <tr>
                            <td>Request Rate (hits, misses)</td>
                            <td><?php echo round($total_hits_and_misses/$memcache_stats['uptime']); ?> cache requests/second</td>
                        </tr>
                        <tr>
                            <td>Hit Rate</td>
                            <td><?php echo round($hits/$memcache_stats['uptime']); ?> cache requests/second</td>
                        </tr>
                        <tr>
                            <td>Miss Rate</td>
                            <td><?php echo round($misses/$memcache_stats['uptime']); ?> cache requests/second</td>
                        </tr>
                        <tr>
                            <td>Set Rate</td>
                            <td><?php echo round($memcache_stats['cmd_set']/$memcache_stats['uptime']); ?> cache requests/second</td>
                        </tr>
                    </table>
                </div>
            </div>
            <?php else: ?>
            <div class="hero-unit">
                <h1>Error connection</h1>
                <p>Ошибка соединения с memcached. Попробуйте позже.</p>
            </div>
            <?php endif; ?>
        </div>
        <script src="js/jquery.js"></script>
        <script src="js/bootstrap.min.js"></script>
        <?php if($memcache !== null): ?>
        <!--[if lte IE 8]><script language="javascript" type="text/javascript" src="js/excanvas.min.js"></script><![endif]-->
	<script language="javascript" type="text/javascript" src="js/jquery.flot.js"></script>
	<script language="javascript" type="text/javascript" src="js/jquery.flot.pie.js"></script>
        <script language="javascript" type="text/javascript" src="js/jquery.flot.categories.js"></script>
	<script type="text/javascript">
            $(function() {
                var memdata = [
                    { label: "Used Cache Size",  data: <?php echo $memcache_stats['bytes']; ?>},
                    { label: "Total Cache Size",  data: <?php echo $memcache_stats['limit_maxbytes'] - $memcache_stats['bytes']; ?>}
                ];
                var hmdata = [ ["Hits", <?php echo $percent_hits; ?>], ["Misses", <?php echo $percent_misses; ?>] ];

                $.plot("#hmstat", [ hmdata ], {
                    series: {
                        bars: {
                            show: true,
                            barWidth: 0.6,
                            align: "center"
                        }
                    },
                    xaxis: {
                        mode: "categories",
                        tickLength: 0
                    },
                    yaxis: {
                        max: 100
                    }
		});
                $.plot('#memstat', memdata, {
                    series: {
                        pie: { 
                            innerRadius: 0.5,
                            show: true
                        }
                    }
                });
                
            })
        </script>
        <?php endif; ?>
    </body>
</html>