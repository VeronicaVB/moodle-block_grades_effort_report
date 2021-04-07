
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

define(['block_grades_effort_report/chart'], function (Chart) {

    function init() {
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
            console.log("Year", p.details.year);
            console.log("Details", p.details.avggrades);

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
            plugins: {
                title: {
                  display: true,
                  text: 'Performance Trend',
                }
              }
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


    }
    return { init: init }
});
