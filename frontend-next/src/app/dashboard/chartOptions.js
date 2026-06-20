

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

    export { chartOptions, barChartOptions, horizontalBarChartOptions, doughnutOptions, timeSeriesChartOptions };