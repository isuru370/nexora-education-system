@extends('layouts.app')

@section('title', 'Exam Results')
@section('page-title', 'Student Exam Results')

@section('breadcrumb')
    <li class="ui-breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="ui-breadcrumb-item"><a href="{{ route('students.index') }}">Students</a></li>
    <li class="ui-breadcrumb-item"><a href="{{ url()->previous() }}">Student Analytics</a></li>
    <li class="ui-breadcrumb-item active">Exam Results</li>
@endsection

@section('content')
    <div class="ui-container-fluid tw:px-2! tw:md:px-4!">
        <!-- Header Card -->
        <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3 bs-row tw:mb-4!">
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:flex-[0_0_auto] tw:w-[100%]">
                <div class="ui-card tw:[border:0]! bs-shadow-sm tw:shadow-bs-sm!">
                    <div class="ui-card-body tw:py-4!">
                        <div class="tw:flex! tw:flex-col! tw:md:flex-row! tw:justify-between! tw:items-start! tw:md:items-center!">
                            <div class="tw:flex! tw:items-center! tw:mb-2! tw:md:mb-0!">
                                <div class="tw:bg-[rgba(13,110,253,0.1)]! tw:p-2! tw:rounded-[50%]! tw:mr-2!">
                                    <i class="fas fa-chart-line tw:text-bs-primary!" style="font-size: 1.2rem;"></i>
                                </div>
                                <div>
                                    <h5 class="tw:mb-0! tw:font-semibold!" id="studentName">Loading...</h5>
                                    <small class="tw:text-bs-secondary-color!" id="className">Please wait</small>
                                </div>
                            </div>
                            <div class="tw:flex! tw:gap-2!">
                                <button class="ui-btn ui-btn-sm ui-btn-outline-primary" onclick="window.print()">
                                    <i class="fas fa-print tw:mr-1!"></i>Print
                                </button>
                                <button class="ui-btn ui-btn-sm ui-btn-outline-success" onclick="exportChart()">
                                    <i class="fas fa-download tw:mr-1!"></i>Export
                                </button>
                                <a href="{{ url()->previous() }}" class="ui-btn ui-btn-sm ui-btn-outline-secondary">
                                    <i class="fas fa-arrow-left tw:mr-1!"></i>Back
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart Card -->
        <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3 bs-row tw:mb-4!">
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:flex-[0_0_auto] tw:w-[100%]">
                <div class="ui-card tw:[border:0]! bs-shadow-sm tw:shadow-bs-sm!">
                    <div class="ui-card-header tw:bg-bs-white! tw:py-2! tw:bs-border-b!">
                        <div class="tw:flex! tw:justify-between! tw:items-center!">
                            <h5 class="tw:mb-0! tw:font-semibold!">
                                <i class="fas fa-chart-bar tw:mr-2! tw:text-bs-primary!"></i>
                                Performance Trend
                            </h5>
                            <div class="ui-btn-group ui-btn-group-sm">
                                <button class="ui-btn ui-btn-outline-secondary" onclick="changeChartType('line')">
                                    <i class="fas fa-chart-line"></i>
                                </button>
                                <button class="ui-btn ui-btn-outline-secondary active" onclick="changeChartType('bar')">
                                    <i class="fas fa-chart-bar"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="ui-card-body tw:p-4!">
                        <!-- Loading State -->
                        <div id="chartLoading" class="tw:text-center! tw:py-6!">
                            <div class="ui-spinner-border tw:text-bs-primary! tw:mb-2!" role="status">
                                <span class="tw:sr-only">Loading...</span>
                            </div>
                            <p class="tw:text-bs-secondary-color! tw:mb-0!">Loading exam data...</p>
                        </div>

                        <!-- Chart Container -->
                        <div id="chartContainer" style="height: 300px; position: relative;" class="tw:hidden!">
                            <canvas id="examChart"></canvas>
                        </div>

                        <!-- No Data State -->
                        <div id="noDataMessage" class="tw:text-center! tw:py-6! tw:hidden!">
                            <div class="tw:bg-bs-light! tw:rounded-[50%]! tw:[display:inline-flex]! tw:p-4! tw:mb-2!">
                                <i class="fas fa-chart-bar tw:text-bs-secondary-color!" style="font-size: 2rem;"></i>
                            </div>
                            <h6 class="tw:text-bs-secondary-color! tw:mb-1!">No Exam Results Found</h6>
                            <p class="tw:text-bs-secondary-color! tw:mb-0!">No exam records available for this student.</p>
                        </div>

                        <!-- Stats Summary -->
                        <div id="statsSummary" class="tw:flex tw:flex-wrap tw:-mt-2 tw:-mx-1 bs-row tw:mt-4! tw:hidden!">
                            <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:flex-[0_0_auto] tw:w-[33.33333333%]">
                                <div class="tw:bg-bs-light! tw:rounded-bs! tw:p-2! tw:text-center!">
                                    <small class="tw:text-bs-secondary-color! tw:block!">Average</small>
                                    <span class="tw:font-bold! bs-fs-5 tw:text-[length:1.1rem]! tw:text-bs-primary!" id="avgMarks">0</span>
                                </div>
                            </div>
                            <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:flex-[0_0_auto] tw:w-[33.33333333%]">
                                <div class="tw:bg-bs-light! tw:rounded-bs! tw:p-2! tw:text-center!">
                                    <small class="tw:text-bs-secondary-color! tw:block!">Highest</small>
                                    <span class="tw:font-bold! bs-fs-5 tw:text-[length:1.1rem]! tw:text-bs-success!" id="highestMarks">0</span>
                                </div>
                            </div>
                            <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:flex-[0_0_auto] tw:w-[33.33333333%]">
                                <div class="tw:bg-bs-light! tw:rounded-bs! tw:p-2! tw:text-center!">
                                    <small class="tw:text-bs-secondary-color! tw:block!">Lowest</small>
                                    <span class="tw:font-bold! bs-fs-5 tw:text-[length:1.1rem]! tw:text-bs-danger!" id="lowestMarks">0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Exam Results Table -->
        <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3 bs-row">
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:flex-[0_0_auto] tw:w-[100%]">
                <div class="ui-card tw:[border:0]! bs-shadow-sm tw:shadow-bs-sm!">
                    <div class="ui-card-header tw:bg-bs-white! tw:py-2! tw:bs-border-b!">
                        <div class="tw:flex! tw:justify-between! tw:items-center!">
                            <h5 class="tw:mb-0! tw:font-semibold!">
                                <i class="fas fa-table tw:mr-2! tw:text-bs-primary!"></i>
                                Exam History
                            </h5>
                            <div class="ui-input-group ui-input-group-sm" style="width: 250px;">
                                <span class="ui-input-group-text tw:bg-bs-white! tw:[border-right:0]!">
                                    <i class="fas fa-search tw:text-bs-secondary-color!"></i>
                                </span>
                                <input type="text" class="ui-form-control tw:[border-left:0]!" 
                                       placeholder="Search exams..." id="searchExams">
                            </div>
                        </div>
                    </div>
                    <div class="ui-card-body tw:p-0!">
                        <!-- Table Loading -->
                        <div id="tableLoading" class="tw:text-center! tw:py-6!">
                            <div class="ui-spinner-border tw:text-bs-primary! tw:mb-2!" role="status">
                                <span class="tw:sr-only">Loading...</span>
                            </div>
                            <p class="tw:text-bs-secondary-color! tw:mb-0!">Loading exam list...</p>
                        </div>

                        <!-- Table Container -->
                        <div id="tableContainer" class="tw:hidden!">
                            <div class="ui-table-responsive">
                                <table class="ui-table ui-table-hover tw:align-middle! tw:mb-0!">
                                    <thead class="tw:bg-bs-light!">
                                        <tr>
                                            <th class="tw:py-2! tw:pl-4!">#</th>
                                            <th class="tw:py-2!">Exam Title</th>
                                            <th class="tw:py-2!">Date</th>
                                            <th class="tw:py-2! tw:text-center!">Marks</th>
                                            <th class="tw:py-2! tw:text-center!">Status</th>
                                            <th class="tw:py-2! tw:text-right! tw:pr-4!">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="examTableBody">
                                        <!-- Data will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Table Footer with Pagination -->
                            <div class="tw:flex! tw:justify-between! tw:items-center! tw:p-2! tw:bg-bs-light! tw:bs-border-t!">
                                <small class="tw:text-bs-secondary-color!" id="tableInfo">Showing 0 exams</small>
                                <div class="ui-btn-group ui-btn-group-sm">
                                    <button class="ui-btn ui-btn-outline-secondary" onclick="prevPage()" id="prevBtn" disabled>
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <span class="ui-btn ui-btn-outline-secondary disabled" id="pageInfo">Page 1 of 1</span>
                                    <button class="ui-btn ui-btn-outline-secondary" onclick="nextPage()" id="nextBtn" disabled>
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* Custom styles */
        .ui-card {
            border-radius: 10px;
            transition: all 0.2s ease;
        }
        
        .ui-btn-group .ui-btn {
            padding: 0.25rem 0.6rem;
        }
        
        .ui-table th {
            font-weight: 600;
            color: #495057;
        }
        
        .ui-badge {
            font-size: 0.75rem;
            padding: 0.35rem 0.65rem;
        }
        
        /* Status badge colors */
        .ui-badge.bs-bg-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important; }
        .ui-badge.bs-bg-info { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important; }
        .ui-badge.bs-bg-warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important; }
        .ui-badge.bs-bg-danger { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important; }
        
        /* Print styles */
        @media print {
            .ui-btn, .ui-btn-group, .ui-input-group, footer, nav, .ui-breadcrumb {
                display: none !important;
            }
            .ui-card {
                border: 1px solid #ddd !important;
                box-shadow: none !important;
            }
        }
        
        /* Chart container */
        #chartContainer {
            min-height: 250px;
        }
        
        /* Font size adjustments */
        body {
            font-size: 0.9rem;
        }
        
        h5 {
            font-size: 1.1rem;
        }
        
        .ui-table td {
            font-size: 0.9rem;
            padding: 0.75rem 0.5rem;
        }
        
        .ui-table th {
            font-size: 0.85rem;
        }
        
        .bs-fs-5 {
            font-size: 1.1rem !important;
        }
        
        small {
            font-size: 0.8rem;
        }
    </style>
@endpush

@push('scripts')
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <!-- html2canvas for export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <script>
        // Get parameters from URL
        const pathParts = window.location.pathname.split('/');
        const classCategoryHasStudentClassId = pathParts[2];  // /students/{id}/{studentId}/exam-results
        const studentId = pathParts[3];
        
        // State management
        let examData = [];
        let filteredData = [];
        let chartInstance = null;
        let currentChartType = 'bar'; // Default to bar chart
        let currentPage = 1;
        const itemsPerPage = 10;
        
        // DOM Elements
        const elements = {
            studentName: document.getElementById('studentName'),
            className: document.getElementById('className'),
            chartLoading: document.getElementById('chartLoading'),
            chartContainer: document.getElementById('chartContainer'),
            noDataMessage: document.getElementById('noDataMessage'),
            statsSummary: document.getElementById('statsSummary'),
            examTableBody: document.getElementById('examTableBody'),
            tableLoading: document.getElementById('tableLoading'),
            tableContainer: document.getElementById('tableContainer'),
            avgMarks: document.getElementById('avgMarks'),
            highestMarks: document.getElementById('highestMarks'),
            lowestMarks: document.getElementById('lowestMarks'),
            searchExams: document.getElementById('searchExams'),
            prevBtn: document.getElementById('prevBtn'),
            nextBtn: document.getElementById('nextBtn'),
            pageInfo: document.getElementById('pageInfo'),
            tableInfo: document.getElementById('tableInfo')
        };
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadExamResults();
            setupEventListeners();
        });
        
        // Setup event listeners
        function setupEventListeners() {
            if (elements.searchExams) {
                let timeout;
                elements.searchExams.addEventListener('keyup', function() {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => {
                        filterExams(this.value.toLowerCase());
                    }, 300);
                });
            }
        }
        
        // Load exam results from API
        async function loadExamResults() {
            try {
                // Show loading states
                if (elements.chartLoading) elements.chartLoading.classList.remove('tw:hidden!');
                if (elements.tableLoading) elements.tableLoading.classList.remove('tw:hidden!');
                if (elements.chartContainer) elements.chartContainer.classList.add('tw:hidden!');
                if (elements.tableContainer) elements.tableContainer.classList.add('tw:hidden!');
                if (elements.noDataMessage) elements.noDataMessage.classList.add('tw:hidden!');
                
                const response = await fetch(`/api/exams/results/${classCategoryHasStudentClassId}/${studentId}`);
                const result = await response.json();
                
                if (result.status === 'success' && result.data && result.data.length > 0) {
                    examData = result.data;
                    filteredData = [...examData];
                    
                    // Update header info
                    updateHeaderInfo();
                    
                    // Render chart (default bar chart)
                    renderChart(examData);
                    
                    // Update statistics
                    updateStatistics(examData);
                    
                    // Render table
                    renderTable();
                    
                    // Hide loading, show content
                    if (elements.chartLoading) elements.chartLoading.classList.add('tw:hidden!');
                    if (elements.tableLoading) elements.tableLoading.classList.add('tw:hidden!');
                    if (elements.chartContainer) elements.chartContainer.classList.remove('tw:hidden!');
                    if (elements.tableContainer) elements.tableContainer.classList.remove('tw:hidden!');
                    if (elements.statsSummary) elements.statsSummary.classList.remove('tw:hidden!');
                } else {
                    // No data
                    if (elements.chartLoading) elements.chartLoading.classList.add('tw:hidden!');
                    if (elements.tableLoading) elements.tableLoading.classList.add('tw:hidden!');
                    if (elements.noDataMessage) elements.noDataMessage.classList.remove('tw:hidden!');
                    
                    // Show default message in header
                    if (elements.studentName) elements.studentName.textContent = 'No Exam Data';
                    if (elements.className) elements.className.textContent = 'No exams found for this student';
                }
            } catch (error) {
                console.error('Error loading exam results:', error);
                showError('Failed to load exam results. Please try again.');
                
                if (elements.chartLoading) elements.chartLoading.classList.add('tw:hidden!');
                if (elements.tableLoading) elements.tableLoading.classList.add('tw:hidden!');
                if (elements.noDataMessage) elements.noDataMessage.classList.remove('tw:hidden!');
            }
        }
        
        // Update header with student/class info
        function updateHeaderInfo() {
            if (elements.studentName) {
                elements.studentName.textContent = 'Exam Performance';
            }
            if (elements.className) {
                elements.className.textContent = `${examData.length} exams found`;
            }
        }
        
        // Generate gradient colors for bar chart
        function getGradient(ctx, color1, color2) {
            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, color1);
            gradient.addColorStop(1, color2);
            return gradient;
        }
        
        // Render chart
        function renderChart(data) {
            const ctx = document.getElementById('examChart').getContext('2d');
            
            // Sort data by date
            const sortedData = [...data].sort((a, b) => new Date(a.date) - new Date(b.date));
            
            const labels = sortedData.map(exam => {
                const date = new Date(exam.date);
                return `${date.getDate()}/${date.getMonth() + 1}`;
            });
            
            const marks = sortedData.map(exam => parseFloat(exam.marks));
            
            // Destroy existing chart
            if (chartInstance) {
                chartInstance.destroy();
            }
            
            // Chart options
            const chartOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#fff',
                        titleColor: '#333',
                        bodyColor: '#666',
                        borderColor: '#ddd',
                        borderWidth: 1,
                        titleFont: { size: 12, weight: 'bold' },
                        bodyFont: { size: 11 },
                        callbacks: {
                            label: function(context) {
                                return `Marks: ${context.raw}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        },
                        ticks: {
                            font: { size: 11 },
                            stepSize: 20,
                            callback: function(value) {
                                return value + '';
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: { size: 11 },
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                }
            };
            
            // Dataset configuration based on chart type
            let dataset;
            
            if (currentChartType === 'bar') {
                // Create gradient for bars
                const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                gradient.addColorStop(0, '#4f46e5');
                gradient.addColorStop(0.5, '#7c3aed');
                gradient.addColorStop(1, '#9333ea');
                
                dataset = {
                    label: 'Marks',
                    data: marks,
                    backgroundColor: gradient,
                    borderRadius: 6,
                    borderSkipped: false,
                    barPercentage: 0.6,
                    categoryPercentage: 0.8
                };
            } else {
                // Line chart
                dataset = {
                    label: 'Marks',
                    data: marks,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    borderWidth: 3,
                    pointBackgroundColor: '#4f46e5',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.3,
                    fill: true
                };
            }
            
            // Create new chart
            chartInstance = new Chart(ctx, {
                type: currentChartType,
                data: {
                    labels: labels,
                    datasets: [dataset]
                },
                options: chartOptions
            });
        }
        
        // Change chart type
        function changeChartType(type) {
            currentChartType = type;
            
            // Update button states
            document.querySelectorAll('[onclick^="changeChartType"]').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.closest('button').classList.add('active');
            
            if (examData.length > 0) {
                renderChart(examData);
            }
        }
        
        // Update statistics
        function updateStatistics(data) {
            const marks = data.map(exam => parseFloat(exam.marks));
            const avg = marks.reduce((a, b) => a + b, 0) / marks.length;
            const highest = Math.max(...marks);
            const lowest = Math.min(...marks);
            
            if (elements.avgMarks) elements.avgMarks.textContent = avg.toFixed(1);
            if (elements.highestMarks) elements.highestMarks.textContent = highest.toFixed(1);
            if (elements.lowestMarks) elements.lowestMarks.textContent = lowest.toFixed(1);
        }
        
        // Render table with pagination
        function renderTable() {
            if (!elements.examTableBody) return;
            
            const start = (currentPage - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            const pageData = filteredData.slice(start, end);
            
            let html = '';
            
            pageData.forEach((exam, index) => {
                const marks = parseFloat(exam.marks);
                const date = new Date(exam.date);
                const formattedDate = date.toLocaleDateString('en-GB', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                });
                
                // Status based on marks
                let statusClass = 'tw:bg-bs-success! bs-bg-success';
                let statusText = 'Excellent';
                if (marks < 40) {
                    statusClass = 'tw:bg-bs-danger! bs-bg-danger';
                    statusText = 'Needs Improvement';
                } else if (marks < 60) {
                    statusClass = 'tw:bg-bs-warning! bs-bg-warning';
                    statusText = 'Average';
                } else if (marks < 75) {
                    statusClass = 'tw:bg-bs-info! bs-bg-info';
                    statusText = 'Good';
                }
                
                html += `
                    <tr>
                        <td class="tw:pl-4! tw:font-medium!">${start + index + 1}</td>
                        <td>
                            <span class="tw:font-medium!">${exam.exam_title}</span>
                            ${exam.is_updated ? '<br><small class="tw:text-bs-secondary-color!"><i class="fas fa-edit tw:mr-1!"></i>Updated</small>' : ''}
                        </td>
                        <td>${formattedDate}</td>
                        <td class="tw:text-center! tw:font-bold! bs-fs-5 tw:text-[length:1.1rem]! ${marks >= 75 ? 'tw:text-bs-success!' : (marks >= 60 ? 'tw:text-bs-info!' : (marks >= 40 ? 'tw:text-bs-warning!' : 'tw:text-bs-danger!'))}">${marks}</td>
                        <td class="tw:text-center!">
                            <span class="ui-badge ${statusClass}">${statusText}</span>
                        </td>
                        <td class="tw:text-right! tw:pr-4!">
                            <button class="ui-btn ui-btn-sm ui-btn-link tw:text-bs-primary! tw:p-0! tw:mr-2!" onclick="viewExamDetails(${exam.exam_id})" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="ui-btn ui-btn-sm ui-btn-link tw:text-bs-success! tw:p-0!" onclick="printExamResult(${exam.exam_id})" title="Print Result">
                                <i class="fas fa-print"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            elements.examTableBody.innerHTML = html;
            
            // Update pagination
            updatePagination();
        }
        
        // Filter exams by search term
        function filterExams(searchTerm) {
            if (!searchTerm) {
                filteredData = [...examData];
            } else {
                filteredData = examData.filter(exam => 
                    exam.exam_title.toLowerCase().includes(searchTerm)
                );
            }
            
            currentPage = 1;
            renderTable();
            
            // Update table info
            if (elements.tableInfo) {
                elements.tableInfo.textContent = `Showing ${filteredData.length} exams`;
            }
        }
        
        // Update pagination controls
        function updatePagination() {
            const totalPages = Math.ceil(filteredData.length / itemsPerPage);
            
            if (elements.pageInfo) {
                elements.pageInfo.textContent = `Page ${currentPage} of ${totalPages || 1}`;
            }
            
            if (elements.prevBtn) {
                elements.prevBtn.disabled = currentPage === 1;
            }
            
            if (elements.nextBtn) {
                elements.nextBtn.disabled = currentPage === totalPages || totalPages === 0;
            }
            
            if (elements.tableInfo) {
                const start = (currentPage - 1) * itemsPerPage + 1;
                const end = Math.min(currentPage * itemsPerPage, filteredData.length);
                elements.tableInfo.textContent = `Showing ${filteredData.length ? start : 0}-${end} of ${filteredData.length} exams`;
            }
        }
        
        // Pagination functions
        function prevPage() {
            if (currentPage > 1) {
                currentPage--;
                renderTable();
            }
        }
        
        function nextPage() {
            const totalPages = Math.ceil(filteredData.length / itemsPerPage);
            if (currentPage < totalPages) {
                currentPage++;
                renderTable();
            }
        }
        
        // Export chart as image
        function exportChart() {
            const chartContainer = document.getElementById('chartContainer');
            if (!chartContainer) return;
            
            html2canvas(chartContainer).then(canvas => {
                const link = document.createElement('a');
                link.download = 'exam-results-chart.png';
                link.href = canvas.toDataURL();
                link.click();
            });
        }
        
        // View exam details
        function viewExamDetails(examId) {
            // You can implement modal or redirect to exam details page
            alert(`View details for exam ID: ${examId} (To be implemented)`);
        }
        
        // Print exam result
        function printExamResult(examId) {
            // You can implement print functionality
            alert(`Print exam ID: ${examId} (To be implemented)`);
        }
        
        // Show error message
        function showError(message) {
            // Simple alert for now - you can enhance with toast
            alert(message);
        }
        
        // Refresh data
        function refreshData() {
            loadExamResults();
        }
    </script>
@endpush