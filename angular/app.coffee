app = angular.module('cf-problems', ['ui.bootstrap'])

app.controller('init', [
    '$scope',
    '$filter',
    ($scope, $filter)->
        contests = {}
        tags = {}
        for i in window.contests.result
            res = i.name.match(/\(Div\.([^\(\)]*)\)/)
            contests[i.id] =
                division: if res and res[1] then res[1] else null

        data = window.problems.result

        for i,k in data.problems
            i.solvedCount = data.problemStatistics[k].solvedCount
            i.contest = contests[i.contestId]
            for j in i.tags
                tags[j] = true

        $scope.tags = Object.keys(tags).sort()

        $scope.problems = data.problems
        $scope.maxSolvedCount = 1000

        $scope.currentPage = 1
        $scope.numPerPage = 30
        $scope.maxSize = 5;
        $scope.order = [
            'contestId'
            'contestId'
            'contestId'
        ]
        $scope.sortOptions =
            'contestId': 'Contest ID'
            '-contestId': 'Contest ID (reversed)'
            'index': 'Problem Index'
            '-index': 'Problem Index (reversed)'
            'name': 'Name'
            '-name': 'Name (reversed)'
            'points': 'Points'
            '-points': 'Points (reversed)'
            'solvedCount': 'Solved'
            '-solvedCount': 'Solved (reversed)'

        $scope.match = (v,i,a)->
            f = $scope.filter
            for k,i of f.tags
                if i
                    if v.tags.indexOf(k) == -1
                        return false
                else
                    delete f.tags[k]

            ok = false
            for k,i of f.div
                if i
                    if v.contest.division*1 == k*1
                        ok = true
                else
                    delete f.div[k]
            if !ok && f.div && Object.keys(f.div).length
                return false

            ok = false
            for k,i of f.index
                if i
                    if v.index == k
                        ok = true
                else
                    delete f.index[k]
            if !ok && f.index && Object.keys(f.index).length
                return false

            return true

        $scope.$watch((() -> $scope.currentPage + ' ' + $scope.numPerPage + ' ' + $scope.maxSize + ' ' + JSON.stringify($scope.filter) + ' ' + JSON.stringify($scope.order)), () ->
            begin = (($scope.currentPage - 1) * $scope.numPerPage)
            end = begin + $scope.numPerPage;

            $scope.filteredProblems = $filter('orderBy')($filter('filter')($scope.problems, $scope.match), $scope.order).slice(begin, end);
        )

        $scope.filter = {}
]);
app.controller('ranking', [
    '$scope',
    '$filter',
    ($scope, $filter)->
        $scope.contests = window.contests
        users = {}
        for k,i of $scope.contests
            for handle,j of i.results
                users[handle] = true
        console.log(users)
        $scope.users = Object.keys(users);

        $scope.order = [
            '-total'
            'handle'
        ]
        $scope.sortOptions =
            'total': 'Total AC'
            '-total': 'Total AC (reversed)'
            'handle': 'Handle'
            '-handle': 'Handle (reversed)'

        $scope.match = (v,i,a)->
            f = $scope.filter

            ok = false
            for k,i of f.type
                if i
                    if v.contest.division*1 == k*1
                        ok = true
                else
                    delete f.div[k]
            if !ok && f.div && Object.keys(f.div).length
                return false

            ok = false
            for k,i of f.index
                if i
                    if v.index == k
                        ok = true
                else
                    delete f.index[k]
            if !ok && f.index && Object.keys(f.index).length
                return false

            return true

        $scope.$watch((() -> JSON.stringify($scope.filter) + ' ' + JSON.stringify($scope.order)), () ->
            begin = (($scope.currentPage - 1) * $scope.numPerPage)
            end = begin + $scope.numPerPage;

            $scope.orderedUsers = $filter('orderBy')($filter('filter')($scope.problems, $scope.match), $scope.order).slice(begin, end);
        )

        $scope.$watch((() -> JSON.stringify($scope.filter) + ' ' + JSON.stringify($scope.order)), () ->
            begin = (($scope.currentPage - 1) * $scope.numPerPage)
            end = begin + $scope.numPerPage;

            $scope.orderedUsers = $filter('orderBy')($filter('filter')($scope.problems, $scope.match), $scope.order).slice(begin, end);
        )

]);