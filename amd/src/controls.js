
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package   block_grades_effort_report
 * @copyright 2021 Veronica Bermegui
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/log', 'core/templates', 'block_grades_effort_report/chart',], function ($, Ajax, Log, Templates, Chart) {
    'use strict';

    function init() {
        const element = document.querySelector('.grade-effort-trend');
        const username = element.getAttribute('data-username');

        var control = new Controls(username);
        control.main();
    }

    /**
    * Controls a single block_grades_effort_report block instance contents.
    *
    * @constructor
    */
    function Controls(username) {
        let self = this;
        self.username = username;
    }

    /**
     * Run the controller.
     *
     */
    Controls.prototype.main = function () {
        let self = this;
        self.trendChart();
        self.setupEvents();
    };

    Controls.prototype.trendChart = function () {

        const ctx = document.getElementById("trendChart");
        const performanceEl = document.querySelector("#performance");
        const performance = JSON.parse(performanceEl.dataset.performance);

        let labels = [];
        let sets = [];
        let attendance = [];
        let effort = [];
        let grades = [];

        const TAGS = {
            avgattendance: 'Attend Average',
            avgeffort: 'Effort Average',
            avggrades: 'Grade Average'
        }

        for (let i = 0; i < performance.length; i++) {
            var p = performance[i];
            console
            let year = p.details.year.toString();

            if (!labels.includes(year)) {
                labels.push(year);
            }

            grades.push(p.details.avggrades);
            effort.push(p.details.avgeffort)
            attendance.push(p.details.avgattendance)

        }

        sets.push({
            label: TAGS.avggrades,
            data: grades,
            fill: false,
            borderColor: '#31326f',
            tension: 0.1
        });

        sets.push({
            label: TAGS.avgeffort,
            data: effort,
            fill: false,
            borderColor: '#ffc93c',
            tension: 0.1
        });

        sets.push({
            label: TAGS.avgattendance,
            data: attendance,
            fill: false,
            borderColor: '#1687a7',
            tension: 0.1
        });

        const data = {
            labels: labels,
            datasets: sets
        };

        const options = {
            responsive: true,
            maintainAspectRatio: false,
        }

        const plugin = {
            id: 'custom_canvas_background_color',
            beforeDraw: (chart) => {
                const ctx = chart.canvas.getContext('2d');
                ctx.save();
                ctx.globalCompositeOperation = 'destination-over';
                ctx.fillStyle = '#f6f5f5';
                ctx.fillRect(0, 0, chart.width, chart.height);
                ctx.restore();
            }
        };

        var myLineChart = new Chart(ctx, {
            type: 'line',
            data: data,
            options: options,
            plugins: [plugin]
        });

    };

    Controls.prototype.setupEvents = function () {
        let self = this;

        $('.grade-history').on('custom.getGradeHistory', function () {
            self.getGradeHistory();
        });

        $('.grade-history').click(function () {
            $(this).trigger("custom.getGradeHistory");
        });

        $('.grade-history').on('custom.historyHideCarretHandler', function () {
            self.historyHideCarretHandler();
        });

        $('.grade-effort').on('custom.getEffortHistory', function () {
            self.getEffortHistory();
        });

        $('.grade-effort').click(function () {
            $(this).trigger("custom.getEffortHistory");
        });

        $('.grade-effort').on('custom.effortHideCarretHandler', function () {
            self.effortHideCarretHandler();
        });

    };

    Controls.prototype.getGradeHistory = function () {
        let self = this;
        const username = self.username;

        // Add spinner.
        $('#grade-history-table').removeAttr('hidden');
        $('#history-show').toggle(); // Carret down.
        $('#history-hide').toggle(); // Carret right

        Ajax.call([{
            methodname: 'block_grades_effort_report_get_context',
            args: {
                table: 'grades',
                username: username
            },

            done: function (response) {
                const htmlResult = response.html;
                const region = $('[data-region="grade-history"]');

                $('#grade-history-table').attr('hidden', true);
                region.replaceWith(htmlResult);
            },

            fail: function (reason) {
                Log.error('block_grades_effort_report: Unable to get context.');
                Log.debug(reason);
                region.replaceWith('<p class="alert alert-danger">Data not available. Please try later</p>');
            }
        }]);

        $('.grade-history').off('custom.getGradeHistory'); // Remove listener.

        $('.grade-history').on('click', function () {
            $(this).trigger("custom.historyHideCarretHandler");
        });
        
    };

    Controls.prototype.getEffortHistory = function () {

        let self = this;
        const username = self.username;

        $('#grade-effort-table').removeAttr('hidden');
        
        $('#effort-show').toggle(); // Carret down is displayed
        $('#effort-hide').toggle(); // Carret right is hidden.

        Ajax.call([{
            methodname: 'block_grades_effort_report_get_context',
            args: {
                table: 'effort',
                username: username
            },

            done: function (response) {
                const htmlResult = response.html;
                const region = $('[data-region="grade-effort"]');

                $('#grade-effort-table').attr('hidden', true);
                region.replaceWith(htmlResult);
                
            },

            fail: function (reason) {
                Log.error('block_grades_effort_report: Unable to get context.');
                Log.debug(reason);
                region.replaceWith('<p class="alert alert-danger">Data not available. Please try later</p>');
            }
            
        }]);

        $('.grade-effort').off('custom.getEffortHistory') // Remove listener.

        $('.grade-effort').on('click', function () {
            $(this).trigger("custom.effortHideCarretHandler");
        });

    };

    Controls.prototype.historyHideCarretHandler = function () {

            $('#history-hide').toggle(); // Carret right. (Table is hidden)
            $('#history-show').toggle(); // Carret down. (Table is displayed)
            console.log("CLICK historyHideCarretHandler");

    }

    Controls.prototype.effortHideCarretHandler = function () {
      
            $('#effort-hide').toggle(); // Carret right. (Table is hidden)
            $('#effort-show').toggle(); // Carret down. (Table is displayed)
            console.log("CLICK effortHideCarretHandler");

    }

    return { init: init }
});
