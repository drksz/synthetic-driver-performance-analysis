"use client";

import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    LineElement,
    PointElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
} from "chart.js";

import { Bar, Line, Doughnut } from "react-chartjs-2";

ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    LineElement,
    PointElement,
    ArcElement,
    Title,
    Tooltip,
    Legend
);

export default function ChartCard({ 
    title, 
    children, 
    className = "",
    titleClassName = "",
}) {
    return (
        <div className={`flex min-w-0 flex-col overflow-hidden rounded-2xl bg-slate-900 p-4 shadow ${className}`}>
            <h2 className={`mb-3 shrink-0 text-xl font-semibold text-slate-200 ${titleClassName}`}>
                {title}
            </h2>
            
            <div className="min-h-0 min-w-0 flex-1 overflow-hidden">
                {children}
            </div>
        </div>
    );
}

export { Bar, Line, Doughnut };