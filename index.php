<?php
const CacheDir = '/tmp';

function get($method){
    $cacheFile = sprintf('%s/%s.json', CacheDir, $method);

    if(file_exists($cacheFile) && filemtime($cacheFile) > time() - 24*60*60){
        $data = file_get_contents($cacheFile);
    }else{
        $data = file_get_contents('http://codeforces.com/api/'.$method);
        file_put_contents($cacheFile, $data);
    }

    return $data;
}

$contests = get('contest.list');
$problems = get('problemset.problems');
?>

<!doctype html>
<!--[if lt IE 7]><html lang="en" class="ie6" ng-app="cf-problems"><![endif]-->
<!--[if IE 7]><html lang="en" class="ie7" ng-app="cf-problems"><![endif]-->
<!--[if IE 8]><html lang="en" class="ie8" ng-app="cf-problems"><![endif]-->
<!--[if gt IE 8]><!-->
<html lang="en" ng-app="cf-problems">
<!--<![endif]-->

<head>
    <meta charset="UTF-8">
    <title>CF problems selector</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />

    <link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="bower_components/angular-bootstrap/ui-bootstrap-csp.css">
    <link rel="stylesheet" href="css/styles.css">

    <script type="text/javascript">
        var problems = <?php echo $problems; ?>;
        var contests = <?php echo $contests; ?>;
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

<body ng-controller="init">
    <div class="wrapper">
        <div class="ranking-filter">
            <form style="padding:10px;">
                <div class="form-group">
                    <label for="exampleInputEmail1">Tags</label>

                    <div style="height:300px;overflow-y: scroll">
                        <div class="checkbox" ng-repeat="i in tags" style="margin-bottom:5px;">
                            <label>
                                <input type="checkbox" ng-model="filter.tags[i]"> {{i}}
                            </label>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="exampleInputEmail1">Division</label>

                    <div class="row">
                        <div class="col-sm-6" ng-repeat="div in [1,2]">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" ng-model="filter.div[div]"> Div. {{div}}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="exampleInputEmail1">Problem Index</label>

                    <div class="row">
                        <div class="col-sm-2" ng-repeat="index in ['A','B','C','D','E']">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" ng-model="filter.index[index]">{{index}}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="exampleInputEmail1">Maximum People Solved</label>
                    <input type="number" ng-model="maxSolvedCount">
                </div>
                <div class="form-group">
                    <label for="exampleInputEmail1">Show on page</label>
                    <select ng-model="numPerPage" ng-options="i for i in [30,60,100,200]"></select>
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
            </form>
        </div>
        <div style="margin-left: 330px;">
            <pagination
                ng-model="currentPage"
                total-items="(problems|filter:match).length"
                max-size="maxSize"
                items-per-page="numPerPage"
                boundary-links="true">
            </pagination>

            <table class="table">
                <tr>
                    <th>#</th>
                    <th>Contest ID</th>
                    <th>Index</th>
                    <th>Name</th>
                    <th>Points</th>
                    <th>Solved</th>
                    <th>Tags</th>
                </tr>
                <tr ng-repeat="(k,i) in filteredProblems">
                    <td>{{(currentPage - 1)*numPerPage + k + 1}}</td>
                    <td><a href="{{'http://codeforces.com/contest/' + i.contestId}}">{{i.contestId}}</a></td>
                    <td><a href="{{'http://codeforces.com/contest/' + i.contestId + '/problem/' + i.index}}">{{i.index}} {{i.contest.division ? "div. " + i.contest.division : ''}}</a></td>
                    <td>{{i.name}}</td>
                    <td>{{i.points}}</td>
                    <td>
                        <div class="progress">
                            <div class="progress-bar" ng-style="{width:(i.solvedCount > maxSolvedCount ? 1 : i.solvedCount/maxSolvedCount)*100 + '%'}"
                                 ng-class="{
                                    'progress-bar-danger':i.solvedCount/maxSolvedCount<0.3,
                                    'progress-bar-warning':i.solvedCount/maxSolvedCount >= 0.3 && i.solvedCount/maxSolvedCount <0.7,
                                    'progress-bar-success':i.solvedCount/maxSolvedCount>=0.7,
                                 }"
                            >
                                {{i.solvedCount}}
                            </div>
                        </div>
                    </td>
                    <td>{{i.tags}}</td>
                </tr>
            </table>
        </div>
    </div>
</body>

</html>
