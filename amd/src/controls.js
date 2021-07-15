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

define(['jquery', 'core/log', 'block_grades_effort_report/chart'], function ($, Log, Chart) {
    'use strict';

    function init() {

        var block = $('[data-block="grades_effort_report"]').first();

        if (!block.length) {
            Log.debug('block_grades_effort_report/control: wrapping region not found!');
            return;
        }

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

        let campus = document.querySelector('.grade-effort-trend').getAttribute('data-campus');

        if (campus == "Senior") {
            self.trendChartSenior();
        } else {
            self.trendChartPrimary();
        }

    };

    Controls.prototype.trendChartSenior = function () {

        const ctx = document.getElementById("trendChart");
        if (!ctx) {
            return;
        }
        const performanceEl = document.querySelector("#performance");
        const performance = JSON.parse(performanceEl.dataset.performance);
        Log.debug(performance);
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

    Controls.prototype.trendChartPrimary = function () {
        const ctx = document.getElementById("trendChart");
        if (!ctx) {
            return;
        }

        const performanceEl = document.querySelector("#performance");
        const performance = JSON.parse(performanceEl.dataset.performance);
       
        let labels = [];
        let sets = [];
        let attendance = [];
        let effort = [];
        let grades = [];

        const TAGS = {
            avgattendance: 'Average Attendance',
            avgeffort: 'Average Effort',
            avggrades: 'Average Grade',
        }

        const TAGS_EFFORTS_DESC = {
            e: 'Excellent (E)',
            vg: 'Very Good (VG)',
            avg: 'Average (AVG)',
            ni: 'Needs Improvement (NI)',
        }

        const TAGS_GRADES_DESC = {
            be: 'Below Expectations',
            gs: 'Good start (GS)',
            ms: 'Making strides (MS)',
            grwi: 'Go run with it (GRWI)',
            ae: 'Above expectations'
        }

        // Segment helpers
        const segments = (dataset) => {
            Log.debug('en segments');
            Log.debug(dataset);
            let hasval = [];
            let noval = [];
            let segment = [];
            
            for (let i = 0; i < dataset.length; i++) {
                
                if (dataset[i] == undefined) {
                    noval.push(i);
                } else {
                    hasval.push(i);
                }
                
            }
            
            for(const index of noval) {
                Log.debug("INDEX");
                Log.debug(index);

                if (index > 0 && index < (dataset.length - 1)) {
                    const seg = [getStartPoint(dataset, index), getEndPoint(dataset, index)];
                    segment.push(seg); //start-finish segment

                }
                
            }
            
            segment = segment.filter(function( element ) {
                return element !== undefined;
            });

            Log.debug(segment);
            
            //ctx.p0.skip || ctx.p1.skip ? value : undefined
        };

        const getEndPoint = (dataset, currindex) => {
            let flag = true;
            let i = ++currindex;
            while (flag && i < dataset.length) {

                if (dataset[i] != undefined)  {
                    flag = false;
                } else {
                    i++;
                }

            }

            return dataset[i];

        };

        const getStartPoint = (dataset, currindex) => {
            let flag = true;
            let i = --currindex;

            while(flag && i > 0) {
                if (dataset[i] != undefined)  {
                    flag = false;
                } else {
                    i--;
                }

            }

            return dataset[i];


        }

        for (let i = 0; i < performance.length; i++) {
            var p = performance[i];

            const year = p.details.year.toString();
            const term = p.details.term.toString();

            labels.push(['T' + term, year]);

            //Log.debug(p.details.avggrades);
            if (p.details.avggrades == null) {
                p.details.avggrades = undefined;
            }
            grades.push(p.details.avggrades);

            if (p.details.effortaverage == null) {
                p.details.effortaverage = undefined;
            }
            effort.push(p.details.effortaverage);

            if (p.details.percentageattended == null) {
                p.details.percentageattended = undefined;
            }
            attendance.push(p.details.percentageattended);

        }
      
        const skipped = (ctx, value) => ctx.p0.skip || ctx.p1.skip ? value : undefined;
        // End Segment helpers
        
        const sgrades = segments(grades);
        const seffort = segments(effort);
        const sattendance = segments( attendance);

        sets.push({
            label: TAGS.avggrades,
            data: grades,
            fill: false,
            borderColor: '#31326f',
            segment: {
                borderDash: ctx => skipped(ctx, sgrades),
            },
            backgroundColor: '#31326f',
            tension: 0.1,
        });

        sets.push({
            label: TAGS.avgeffort,
            data: effort,
            fill: false,
            borderColor: '#ffc93c',
            segment: {
                borderDash: ctx => skipped(ctx, seffort),
            },
            backgroundColor: '#ffc93c',
            tension: 0.1
        });

        sets.push({
            label: TAGS.avgattendance,
            data: attendance,
            fill: false,
            borderColor: '#1687a7',
             segment: {
                 borderDash: ctx => skipped(ctx, sattendance),
             },
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
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function (context) {

                            if (context.dataset.label == TAGS.avggrades) {

                                if (context.parsed.y > 0.00 && context.parsed.y <= 20.00) {
                                    return TAGS_GRADES_DESC.be;
                                } else if (context.parsed.y > 20.00 && context.parsed.y <= 40.00) {
                                    return TAGS_GRADES_DESC.gs
                                } else if (context.parsed.y > 40.00 && context.parsed.y <= 60.00) {
                                    return TAGS_GRADES_DESC.ms;
                                } else if (context.parsed.y > 60.00 && context.parsed.y <= 80.00) {
                                    return TAGS_GRADES_DESC.grwi;
                                } else if (context.parsed.y > 80.00) {
                                    return TAGS_GRADES_DESC.ae
                                }
                            }
                            if (context.dataset.label == TAGS.avgeffort) {
                                Log.debug(context.dataset.label);
                                Log.debug(context.parsed.y);
                                if (parseFloat(context.parsed.y) > 0.00 && parseFloat(context.parsed.y) <= 25.00) {
                                    return TAGS_EFFORTS_DESC.ni;
                                } else if (parseFloat(context.parsed.y) > 25.00 && parseFloat(context.parsed.y) <= 50.00) {
                                    return TAGS_EFFORTS_DESC.avg
                                } else if (parseFloat(context.parsed.y) > 50.00 && parseFloat(context.parsed.y) < 95.00) {
                                    return TAGS_EFFORTS_DESC.vg;
                                } else if (parseFloat(context.parsed.y) >= 95.00) {
                                    return TAGS_EFFORTS_DESC.e;
                                }
                            } else {

                                return context.parsed.y + "%";
                            }
                        }
                    }
                }
            },
            scales: {
                y: {
                    suggestedMin: 25,
                    display: false,

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
            plugins: [plugin],

        });
    }


    return {
        init: init
    }
});