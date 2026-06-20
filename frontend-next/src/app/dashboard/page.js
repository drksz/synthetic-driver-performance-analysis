"use client";

import {useEffect, useState } from "react";
import ChartCard, { Bar, Line, Doughnut } from "../../components/ChartCard";

const API_BASE = "http://127.0.0.1:8000/api";

export default function DashboardPage() {
    const [data, setData] = useState(null);
    const [error, setError] = useState(null);
    const [startDate, setStartDate] = useState("2026-01-01");
    const [endDate, setEndDate] = useState("2026-06-01");
    const [traffic, setTraffic] = useState("");
    const[priority, setPriority] = useState("");

    async function fetchDashboardData() {
        try {

            const query = new URLSearchParams({
                start_date: startDate,
                end_date: endDate,
                traffic,
                priority,
            });

            const endpoints = [
                "kpis",
                "weather-stats",
                "vehicle-stats",
                "priority-stats",
                "weekday-delay",
                "week-total-delay",
                "month-total-delay",
                "rating-summary",
            ];

            const responses = await Promise.all(
                endpoints.map((endpoint) => fetch(`${API_BASE}/${endpoint}?${query}`))
            );

            for (const res of responses) {
                if (!res.ok) throw new Error("Failed to fetch dashboard data");
            }

            const [
                kpis,
                weatherStats,
                vehicleStats,
                priorityStats,
                weekdayDelay,
                weekTotalDelay,
                monthTotalDelay,
                ratingSummary,
            ] = await Promise.all(responses.map((res) => res.json()));

            setData({
                kpis: kpis[0],
                weatherStats,
                vehicleStats,
                priorityStats,
                weekdayDelay,
                weekTotalDelay,
                monthTotalDelay,
                ratingSummary,
            })
            } catch(err) {
                setError(err.message);
            }
        }

    useEffect(() => {
        fetchDashboardData();
    }, []);

    if (error) return <main className="p-8 text-red-500">Error: {error}</main>;
    if (!data) return <main className="p-8">Loading dashboard...</main>;

    const gridColor = "#1d2838";
    const tickColor = "#cbd5e1";

    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    color: tickColor,
                },
            },
        },
        scales: {
            x: {
                ticks: { color: tickColor },
            },
            y: {
                ticks: { color: tickColor },
                grid: { color: gridColor },
            },
        },
    };

    const barChartOptions = {
        ...chartOptions,
        scales: {
            ...chartOptions.scales,
            y: {
                'title': {
                    display: true,
                    text: 'mins.',
                    font: { weight: 'bold', size: 13 },
                    color: tickColor,
                },
                ticks: { 
                    color: tickColor, 
                    stepSize: 5,
                },
                grid: { color: gridColor },
            },
        },
        plugins: {
            ...chartOptions.plugins,
            legend: {
                display:false,
                labels: {
                    color: tickColor,
                },
            },
        },
    };


    const timeSeriesChartOptions = {
        ...chartOptions, 
        scales: {
            ...chartOptions.scales,
            y: {
                title: {
                    display: true,
                    text: 'mins.',
                    color: tickColor,
                    font: { weight: 'bold', size: 13 },
                },
                ticks: { color: tickColor },
                grid: { color: gridColor },
            },
        },
        plugins: {
            legend: {
                display: false,
            },
        },
    };

    const horizontalBarChartOptions = {
        ...barChartOptions,
        indexAxis: "y",
        scales: {
            ...barChartOptions.scales,
            x: {
                ticks: { stepSize: 0.5 },
            },
            y: {
                title: { display: false },
                ticks: { color: tickColor },
                grid: { color: gridColor },
            },
        },
    };

    const doughnutOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    color: tickColor,
                },
                position: 'bottom',
                align: 'center',
            },
        },
    };

    return (
        <main className="min-h-screen bg-slate-950 p-5 text-white">


            <div className="mb-6 flex items-start justify-between">

                <div>
                    <h1 className="text-5xl font-bold">Delivery Performance Dashboard</h1>
                    <p className="mt-2 text-lg text-slate-400">
                        Overview of delivery performance metrics from January to June 2026.
                    </p>
                </div>


                <div className="flex items-end gap-4 pb-5">
                    <div>
                        <label className="mb-1 block text-sm text-slate-300">Start Date</label>

                        <input
                            type="date"
                            value={startDate}
                            min="2026-01-01"
                            max="2026-06-30"
                            onChange={(e) => setStartDate(e.target.value)}
                            className="rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-slate-200"
                        />
                    </div>

                    <div>
    
                        <label className="mb-1 block text-sm text-slate-300">
                            End Date
                        </label>

                        <input
                            type="date"
                            value={endDate}
                            min="2026-01-01"
                            max="2026-06-30"
                            onChange={(e) => setEndDate(e.target.value)}
                            className="rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-slate-200"
                        />
                    </div>

                    <div>
                        <label className="mb-1 block text-sm text-slate-3000">
                            Traffic
                        </label>

                        <select
                            value={traffic}
                            onChange={(e) => setTraffic(e.target.value)}
                            className="rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-slate-200"
                        >
                            <option value="">All</option>
                            <option value="Light">Light</option>
                            <option value="Medium">Medium</option>
                            <option value="Heavy">Heavy</option>
                        </select>
                    </div>

                    <div>
                        <label className="mb-1 block text-sm text-slate-300">
                            Priority
                        </label>

                        <select
                            value={priority}
                            onChange={(e) => setPriority(e.target.value)}
                            className="rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-slate-200" 
                        >
                            <option value="">All</option>
                            <option value="Standard">Standard</option>
                            <option value="Express">Express</option>
                        </select>
                    </div>

                    <button
                        onClick={fetchDashboardData}
                        className="rounded-lg bg-indigo-600 px-4 py-2 text-white font-medium hover:bg-indigo-700"
                    >Apply Filters
                    </button>
                    
                </div>

            </div>

            
            <section className="grid grid-cols-3 gap-5">

                <KpiCard title="Total Deliveries" value={new Intl.NumberFormat('en-US').format(data.kpis.total_deliveries)} />
                <KpiCard title="Overall Median Delay" value={`${Number(data.kpis.median_delay_mins).toFixed(2)} mins`} />
                <KpiCard title="On-Time Percentage" value={`${data.kpis.on_time_percentage}%`} />


            
                <ChartCard title="Vehicle Type Proportion" className="h-[370px]">
                    <div className="flex h-full items-center justify-center">
                        <div className="h-[310px] w-[390px]">

                            <Doughnut
                                options={doughnutOptions}
                                data={{
                                    labels: data.vehicleStats.map((item) => item.vehicle_type),
                                    datasets: [
                                        {
                                            data: data.vehicleStats.map((item) => item.proportion),
                                            backgroundColor: [
                                                "#3F51B5",
                                                "#673AB7",
                                                "#9C27B0",
                                            ],
                                            borderColor: "#0f172a",
                                            borderWidth: 4,
                                        },
                                    ],
                                }}
                            />

                        </div>
                    </div>
                </ChartCard>

                
                <ChartCard title="Median Delay by Weekday" className="h-[370px]">
                <Bar
                    options={barChartOptions}
                    data={{
                        labels: data.weekdayDelay.map((item) => item.weekday),
                        datasets: [
                            {
                            label: "Median delay",
                            data: data.weekdayDelay.map((item) => item.median_delay_mins),
                            backgroundColor: "#7033b6",
                            },
                        ],
                    }}
                />
                </ChartCard>


                <ChartCard title="Median Delay by Weather" className="h-[370px]">
                <Bar
                    options={barChartOptions}
                    data={{
                        labels: data.weatherStats.map((item) => item.weather),
                        datasets: [
                            {
                            label: "Median delay (mins)",
                            data: data.weatherStats.map((item) => item.median_delay_mins),
                            backgroundColor: [
                                "#FF9800",
                                "#3F51B5",
                            ],
                            },
                        ],
                    }}
                />
                </ChartCard>

                <div className="col-span-3 grid h-[520px] grid-cols-[minmax(0,1.8fr)_minmax(0,1fr)] gap-5">

                    <div className="grid min-w-0 min-h-0 grid-rows-2 gap-5">
                        <ChartCard title="Total Weekly Delay" className="min-h-0" titleClassName="text-center">
                            <Line
                                options={timeSeriesChartOptions}
                                data={{
                                    labels: data.weekTotalDelay.map((item) =>
                                        new Date(item.week_start).toLocaleDateString()
                                    ),
                                    datasets: [
                                        {
                                        label: "Total delay (mins)",
                                        data: data.weekTotalDelay.map((item) => item.total_delay_mins),
                                        backgroundColor: "#b66060",
                                        borderColor: "#b66060",
                                        tension: 0.67,
                                        },
                                    ],
                                }}
                            />
                        </ChartCard>


                        <ChartCard title="Total Monthly Delay" className="min-h-0" titleClassName="text-center">
                            <Line
                                options={timeSeriesChartOptions}
                                data={{
                                    labels: data.monthTotalDelay.map((item) =>
                                        new Date(item.month_start).toLocaleDateString()
                                    ),
                                    datasets: [
                                        {
                                        label: "Total delay (mins)",
                                        data: data.monthTotalDelay.map((item) => item.total_delay_mins),
                                        backgroundColor: "#01579B",
                                        borderColor: "#01579B",
                                        tension: 0.3,
                                        },
                                    ],
                                }}
                            />
                        </ChartCard>
                    </div>

                    <ChartCard title="Average Driver Ratings" className="min-w-0 h-full" titleClassName="text-center">
                        <Bar
                            options={horizontalBarChartOptions}
                            data={{
                                labels: data.ratingSummary.map((item) => item.rating_name),
                                datasets: [
                                    {
                                    label: "Average rating",
                                    data: data.ratingSummary.map((item) => item.average),
                                    backgroundColor: [
                                        "#FFE0B2",
                                        "#F57C00",
                                        "#FF9800",
                                        "#FFB74D",
                                        
                                    ],
                                    },
                                ],
                            }}
                        />
                    </ChartCard>

                </div>
        

            </section>            
        </main>
    );
}

function KpiCard({ title,value }) {
    return (
        <div className="flex flex-col justify-center rounded-xl bg-slate-900 p-6 shadow">
            <p className="mt-2 text-sm pb-2 tracking-wide uppercase text-slate-400">{title}</p>
            <h2 className="text-5xl font-bold text-white">{value}</h2>
        </div>
    );
}