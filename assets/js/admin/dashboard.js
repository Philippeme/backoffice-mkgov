// assets/js/admin/dashboard.js

class MKGovDashboard {
    constructor() {
        this.charts = {};
        this.map = null;
        this.currentFilters = {};
        this.refreshInterval = 300000; // 5 minutes
        this.autoRefreshEnabled = true;
    }

    init() {
        this.initializeEventListeners();
        this.initializeMap();
        this.loadDashboardData();
        this.initializeCharts();

        if (this.autoRefreshEnabled) {
            this.startAutoRefresh();
        }
    }

    initializeEventListeners() {
        // Filter controls
        document.getElementById('applyFilters')?.addEventListener('click', () => {
            this.applyFilters();
        });

        // Refresh button
        document.getElementById('refreshDashboard')?.addEventListener('click', () => {
            this.refreshDashboard();
        });

        // Export functionality
        document.querySelectorAll('[data-export]').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.exportData(button.dataset.export);
            });
        });

        // Sidebar toggle
        document.querySelector('.sidebar-toggle')?.addEventListener('click', () => {
            this.toggleSidebar();
        });

        // Navigation dropdown toggles
        document.querySelectorAll('.nav-dropdown-toggle').forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleNavDropdown(toggle.parentElement);
            });
        });
    }

    initializeMap() {
        // Initialize Leaflet map for Cameroon
        this.map = L.map('cameroonMap').setView([7.3697, 12.3547], 6);

        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(this.map);

        // Cameroon regions coordinates
        const cameroonRegions = [
            { name: 'Centre', coords: [3.8667, 11.5167], requests: 450 },
            { name: 'Littoral', coords: [4.0511, 9.7679], requests: 380 },
            { name: 'Ouest', coords: [5.4667, 10.5000], requests: 290 },
            { name: 'Nord', coords: [9.3265, 13.3833], requests: 180 },
            { name: 'Adamaoua', coords: [6.5000, 12.5000], requests: 120 },
            { name: 'Est', coords: [4.0000, 14.0000], requests: 95 },
            { name: 'Nord-Ouest', coords: [6.2000, 10.2500], requests: 160 },
            { name: 'Sud-Ouest', coords: [4.6167, 9.2667], requests: 140 },
            { name: 'Sud', coords: [2.7833, 11.5000], requests: 85 },
            { name: 'Extrême-Nord', coords: [10.5833, 14.2167], requests: 200 }
        ];

        // Add markers for each region
        cameroonRegions.forEach(region => {
            const intensity = this.getIntensityColor(region.requests);

            const marker = L.circleMarker(region.coords, {
                radius: Math.max(8, region.requests / 20),
                fillColor: intensity,
                color: '#fff',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.8
            }).addTo(this.map);

            // Add popup with region information
            marker.bindPopup(`
                <div class="map-popup">
                    <h6>${region.name}</h6>
                    <p>Active Requests: <strong>${region.requests}</strong></p>
                    <p>Status: <span class="badge bg-${this.getStatusBadge(region.requests)}">${this.getRegionStatus(region.requests)}</span></p>
                </div>
            `);
        });
    }

    getIntensityColor(requests) {
        if (requests > 300) return '#28a745'; // High activity - Green
        if (requests > 150) return '#ffc107'; // Medium activity - Yellow
        return '#dc3545'; // Low activity - Red
    }

    getStatusBadge(requests) {
        if (requests > 300) return 'success';
        if (requests > 150) return 'warning';
        return 'danger';
    }

    getRegionStatus(requests) {
        if (requests > 300) return 'High Activity';
        if (requests > 150) return 'Medium Activity';
        return 'Low Activity';
    }

    async loadDashboardData() {
        try {
            const response = await fetch(window.MKGovAdmin.routes.dashboard_stats + this.buildQueryString());
            const data = await response.json();

            if (data.success) {
                this.updateStatistics(data.data);
                this.updateRecentRequests(data.data.recent_requests);
            }
        } catch (error) {
            console.error('Error loading dashboard data:', error);
            this.showNotification('Error loading dashboard data', 'error');
        }
    }

    updateStatistics(stats) {
        // Update statistics cards
        this.animateCounter('totalRequests', stats.total_requests || 0);
        this.animateCounter('completedRequests', stats.completed_requests || 0);
        this.animateCounter('pendingRequests', stats.pending_requests || 0);
        this.animateCounter('activeUsers', stats.active_users || 0);

        // Update percentage changes
        document.getElementById('requestsChange').textContent = `${stats.requests_change > 0 ? '+' : ''}${stats.requests_change}%`;
        document.getElementById('completedChange').textContent = `${stats.completed_change > 0 ? '+' : ''}${stats.completed_change}%`;
        document.getElementById('pendingChange').textContent = `${stats.pending_change > 0 ? '+' : ''}${stats.pending_change}%`;
        document.getElementById('usersChange').textContent = `${stats.users_change > 0 ? '+' : ''}${stats.users_change}%`;
    }

    animateCounter(elementId, targetValue) {
        const element = document.getElementById(elementId);
        if (!element) return;

        const startValue = parseInt(element.textContent) || 0;
        const duration = 1000;
        const increment = (targetValue - startValue) / (duration / 16);
        let currentValue = startValue;

        const animate = () => {
            currentValue += increment;
            if ((increment > 0 && currentValue >= targetValue) || (increment < 0 && currentValue <= targetValue)) {
                element.textContent = targetValue.toLocaleString();
                return;
            }
            element.textContent = Math.floor(currentValue).toLocaleString();
            requestAnimationFrame(animate);
        };

        animate();
    }

    initializeCharts() {
        this.initializeServicesPieChart();
        this.initializeRequestsTimelineChart();
        this.initializeStatusDistributionChart();
    }

    initializeServicesPieChart() {
        const ctx = document.getElementById('servicesPieChart');
        if (!ctx) return;

        this.charts.servicesPie = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Police & Justice', 'Family', 'Transport', 'Education', 'Business'],
                datasets: [{
                    data: [30, 25, 20, 15, 10],
                    backgroundColor: [
                        '#1a73e8',
                        '#34a853',
                        '#ff6d01',
                        '#9c27b0',
                        '#607d8b'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
    }

    initializeRequestsTimelineChart() {
        const ctx = document.getElementById('requestsTimelineChart');
        if (!ctx) return;

        const labels = this.generateDateLabels(30);
        const data = this.generateTimelineData(30);

        this.charts.requestsTimeline = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total Requests',
                    data: data.total,
                    borderColor: '#1a73e8',
                    backgroundColor: 'rgba(26, 115, 232, 0.1)',
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Completed',
                    data: data.completed,
                    borderColor: '#34a853',
                    backgroundColor: 'rgba(52, 168, 83, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                }
            }
        });
    }

    initializeStatusDistributionChart() {
        const ctx = document.getElementById('statusDistributionChart');
        if (!ctx) return;

        this.charts.statusDistribution = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Pending', 'Processing', 'Completed', 'Rejected'],
                datasets: [{
                    data: [45, 32, 78, 12],
                    backgroundColor: [
                        '#ffc107',
                        '#17a2b8',
                        '#28a745',
                        '#dc3545'
                    ],
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    generateDateLabels(days) {
        const labels = [];
        for (let i = days - 1; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            labels.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
        }
        return labels;
    }

    generateTimelineData(days) {
        const total = [];
        const completed = [];

        for (let i = 0; i < days; i++) {
            const totalValue = Math.floor(Math.random() * 50) + 20;
            total.push(totalValue);
            completed.push(Math.floor(totalValue * (0.6 + Math.random() * 0.3)));
        }

        return { total, completed };
    }

    applyFilters() {
        const form = document.getElementById('dashboardFilters');
        const formData = new FormData(form);

        this.currentFilters = {
            region: formData.get('region'),
            service_family: formData.get('service_family'),
            status: formData.get('status'),
            year: formData.get('year')
        };

        this.loadDashboardData();
        this.updateMapData();
        this.updateChartsData();

        this.showNotification('Filters applied successfully', 'success');
    }

    async updateMapData() {
        try {
            const response = await fetch(window.MKGovAdmin.routes.dashboard_map + this.buildQueryString());
            const data = await response.json();

            if (data.success) {
                // Update map markers with filtered data
                this.updateMapMarkers(data.data);
            }
        } catch (error) {
            console.error('Error updating map data:', error);
        }
    }

    async updateChartsData() {
        try {
            // Update each chart with filtered data
            const chartTypes = ['requests', 'procedures', 'users', 'entities'];

            for (const type of chartTypes) {
                const response = await fetch(
                    window.MKGovAdmin.routes.dashboard_charts.replace('TYPE', type) + this.buildQueryString()
                );
                const data = await response.json();

                if (data.success && this.charts[`${type}Chart`]) {
                    this.updateChartData(this.charts[`${type}Chart`], data.data);
                }
            }
        } catch (error) {
            console.error('Error updating charts data:', error);
        }
    }

    updateChartData(chart, newData) {
        chart.data.datasets[0].data = newData.values;
        if (newData.labels) {
            chart.data.labels = newData.labels;
        }
        chart.update('active');
    }

    buildQueryString() {
        const params = new URLSearchParams();

        Object.entries(this.currentFilters).forEach(([key, value]) => {
            if (value) {
                params.append(key, value);
            }
        });

        return params.toString() ? '?' + params.toString() : '';
    }

    refreshDashboard() {
        const button = document.getElementById('refreshDashboard');
        const icon = button.querySelector('i');

        // Add spinning animation
        icon.classList.add('fa-spin');
        button.disabled = true;

        Promise.all([
            this.loadDashboardData(),
            this.updateMapData(),
            this.updateChartsData()
        ]).then(() => {
            this.showNotification('Dashboard refreshed successfully', 'success');
        }).catch((error) => {
            console.error('Error refreshing dashboard:', error);
            this.showNotification('Error refreshing dashboard', 'error');
        }).finally(() => {
            icon.classList.remove('fa-spin');
            button.disabled = false;
        });
    }

    startAutoRefresh() {
        setInterval(() => {
            this.loadDashboardData();
        }, this.refreshInterval);
    }

    exportData(format) {
        const exportData = {
            statistics: this.getCurrentStatistics(),
            filters: this.currentFilters,
            timestamp: new Date().toISOString()
        };

        switch (format) {
            case 'pdf':
                this.exportToPDF(exportData);
                break;
            case 'excel':
                this.exportToExcel(exportData);
                break;
            case 'csv':
                this.exportToCSV(exportData);
                break;
        }
    }

    exportToPDF(data) {
        // PDF export implementation would go here
        this.showNotification('PDF export functionality coming soon', 'info');
    }

    exportToExcel(data) {
        // Excel export implementation would go here
        this.showNotification('Excel export functionality coming soon', 'info');
    }

    exportToCSV(data) {
        // CSV export implementation would go here
        this.showNotification('CSV export functionality coming soon', 'info');
    }

    toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('show');
    }

    toggleNavDropdown(navItem) {
        navItem.classList.toggle('open');
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(notification);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }

    getCurrentStatistics() {
        return {
            totalRequests: document.getElementById('totalRequests')?.textContent || '0',
            completedRequests: document.getElementById('completedRequests')?.textContent || '0',
            pendingRequests: document.getElementById('pendingRequests')?.textContent || '0',
            activeUsers: document.getElementById('activeUsers')?.textContent || '0'
        };
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', function () {
    window.dashboard = new MKGovDashboard();
    window.dashboard.init();
});