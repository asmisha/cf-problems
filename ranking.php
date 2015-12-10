<?php

require_once(__DIR__ . '/lib/simple_html_dom.php');
require_once(__DIR__ . '/lib/cf.php');
$config = require_once(__DIR__ . '/config.php');

$cacheFile = __DIR__ . '/cache/standings.tgz';
if (!file_exists($cacheFile) || filemtime($cacheFile) < time() - 24 * 60 * 60) {
    exec(sprintf('cd %s && wget contest.bsuir.by/export/standings.tgz -qO standings.tgz && tar -xvf standings.tgz', __DIR__ . '/cache'));
}

$contests = array();
$files = glob(__DIR__ . '/cache/*.html', GLOB_BRACE);
foreach ($files as $file) {
    $dom = file_get_html($file);
    $i = 0;
    $res = array();
    foreach ($dom->find('table.standings tr') as $line) {
        $place = @$line->find('td.st_place', 0)->plaintext;

        if (!preg_match('#^\d+$#', $place) && !preg_match('#^\d+\-\d+$#', $place)) {
            continue;
        }

        $res[$line->find('td.st_team', 0)->next_sibling()->plaintext] = array(
            'place' => $place,
            'total' => $line->find('td.st_total', 0)->plaintext,
        );
    }

    $type = strstr(basename($file), 'practice') === false ? 'training' : 'practice';
    $contests['cbb_' . basename($file, '.html')] = array(
        'type' => $type,
        'info' => basename($file, '.html'),
        'source' => 'bsuir',
        'results' => $res,
        'date' => filemtime($file),
        'weight' => $config['weight'][$type],
    );
}

foreach ($config['coaches'] as $coach) {
    foreach ($coach['contests'] as $contest) {
        $standings = json_decode(get('contest.standings', array('contestId' => $contest, 'showUnofficial' => true), $coach['auth']))->result;
        $contests["cf_{$contest}_training"] = array(
            'type' => 'training',
            'info' => "cf {$standings->contest->name}",
            'source' => 'cf',
            'results' => array(),
            'date' => $standings->contest->startTimeSeconds,
            'weight' => $config['weight']['training'],
        );
        $contests["cf_{$contest}_practice"] = array(
            'type' => 'practice',
            'info' => "cf {$standings->contest->name}",
            'source' => 'cf',
            'results' => array(),
            'date' => $standings->contest->startTimeSeconds,
            'weight' => $config['weight']['practice'],
        );

        foreach ($standings->rows as $row) {
            $type = $row->party->participantType == 'CONTESTANT' ? 'training' : 'practice';

            $contests["cf_{$contest}_$type"]['results'][$row->party->members[0]->handle] = array(
                'place' => $row->rank,
                'total' => $row->points
            );
        }
    }
}
$contests = array_values($contests);
?>

<!doctype html>
<!--[if lt IE 7]>
<html lang="en" class="ie6" ng-app="cf-problems"><![endif]-->
<!--[if IE 7]>
<html lang="en" class="ie7" ng-app="cf-problems"><![endif]-->
<!--[if IE 8]>
<html lang="en" class="ie8" ng-app="cf-problems"><![endif]-->
<!--[if gt IE 8]><!-->
<html lang="en" ng-app="cf-problems">
<!--<![endif]-->

<head>
    <meta charset="UTF-8">
    <title>BSUIR Olymp Ranking</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>

    <link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="bower_components/angular-bootstrap/ui-bootstrap-csp.css">
    <link rel="stylesheet" href="css/styles.css">

    <script type="text/javascript">
        var contests = <?php echo json_encode($contests); ?>;
    </script>

    <script type="text/javascript" src="bower_components/angular/angular.min.js"></script>
    <script type="text/javascript" src="bower_components/angular-bootstrap/ui-bootstrap.min.js"></script>
    <script type="text/javascript" src="bower_components/angular-bootstrap/ui-bootstrap-tpls.min.js"></script>

    <script type="text/javascript" src="angular/app.js"></script>

    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>

<body ng-controller="ranking">
<div class="wrapper">
    <div class="ranking-filter">
        <form style="padding:10px;">
            <div class="form-group">
                <label for="exampleInputEmail1">Contest Type</label>

                <div class="row">
                    <div class="col-sm-6" ng-repeat="type in ['practice','training']">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" ng-model="filter.type[type]"> {{type}}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="exampleInputEmail1">Order by</label>
                <select ng-model="order[0]" ng-options="k as i for (k,i) in sortOptions"></select>
            </div>
            <div class="form-group">
                <label for="exampleInputEmail1">Then by</label>
                <select ng-model="order[1]" ng-options="k as i for (k,i) in sortOptions"></select>
            </div>
            <div class="form-group">
                <label for="exampleInputEmail1">Then by</label>
                <select ng-model="order[2]" ng-options="k as i for (k,i) in sortOptions"></select>
            </div>
            <div class="form-group">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" ng-model="showDates"> Show Dates
                    </label>
                </div>
            </div>
        </form>
    </div>
    <div style="margin-left: 330px;">
        <table class="table table-bordered">
            <tr>
                <th>#</th>
                <th>Handle</th>
                <th>Total AC</th>
                <th>Rating</th>
                <th class="contest-source" ng-repeat="i in contests|filter:contestMatch|orderBy:['date']">
                    <img src="img/{{i.source}}.png" title="{{i.info}}">
                    <img src="img/{{i.type}}.png" title="{{i.type}}">
                    <span ng-show="showDates">{{(i.date*1000)|date:'dd.MM.yyyy'}}</span>
                </th>
            </tr>
            <tr ng-repeat="(k,i) in users|orderBy:order">
                <td>{{k+1}}</td>
                <td>{{i.handle}}</td>
                <td>{{i.total}}</td>
                <td>{{i.rating | number : 3}}</td>
                <td ng-repeat="j in contests|filter:contestMatch|orderBy:['date']">{{j.results[i.handle].total}}</td>
            </tr>
        </table>
    </div>
</div>
</body>

</html>
