
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

define(['jquery', 'core/ajax', 'core/log', 'block_grades_effort_report/chart'], function ($, Ajax, Log, Chart) {
    'use strict';

    function init() {
        var control = new Controls();
        control.main();
    }

    /**
    * Controls a single block_grades_effort_report block instance contents.
    *
    * @constructor
    */
    function Controls() {
        
    }

    /**
     * Run the controller.
     *
     */
    Controls.prototype.main = function () {
        let self = this;
        self.trendChart();

    };

    Controls.prototype.trendChart = function () {

        const ctx = document.getElementById("trendChart");
        const performanceEl = document.querySelector("#performance");
        if (performanceEl.dataset.performance === "") { // no data available.
            const pEl = document.createElement('p');
            pEl.innerHTML = 'Trend Data not available';
            ctx.parentNode.replaceChild(pEl, ctx);
            return;
        }
        const performance = JSON.parse(performanceEl.dataset.performance);
        console.log(performance);
        let labels = [];
        let sets = [];
        let attendance = [];
        let effort = [];
        let grades = [];
        let gradeperterm = [];

        const TAGS = {
            avgattendance: 'Average Attendance',
            avgeffort: 'Average Effort',
            avggrades: 'Average Grade',
            avgradeterm: 'Average Grade',
        }

        for (let i = 0; i < performance.length; i++) {
            var p = performance[i];

            const year = p.details.year.toString();
            const term = p.details.term.toString();

            labels.push(['T' + term, year]);
            gradeperterm.push(p.details.avggrades);

            grades.push(p.details.avggrades);
            effort.push(p.details.avgeffort)
            attendance.push(p.details.avgattendance)

        }

        sets.push({
            label: TAGS.avggrades,
            data: grades,
            fill: false,
            borderColor: '#31326f',
            backgroundColor: '#31326f',
            tension: 0.1,
        });

        sets.push({
            label: TAGS.avgeffort,
            data: effort,
            fill: false,
            borderColor: '#ffc93c',
            backgroundColor: '#ffc93c',
            tension: 0.1
        });

        sets.push({
            label: TAGS.avgattendance,
            data: attendance,
            fill: false,
            borderColor: '#1687a7',
            backgroundColor: '#1687a7',
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
            plugins: [plugin],

        });

    };
    

    return { init: init }
});
