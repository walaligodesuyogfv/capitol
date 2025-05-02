# Reports Module Documentation

## Overview
The Reports module is a comprehensive reporting system integrated with Python-based analytics and machine learning predictions. It allows users to generate, view, export, and schedule various types of reports related to inventory, purchase orders (PO), and property acknowledgement receipts (PAR).

## Key Features
1. **Summary Reports**: Provides high-level metrics such as total assets per department, PAR vs PO values, and asset distributions.
2. **Detailed Reports**: Includes detailed information for PARs, POs, inventory items, and audit logs.
3. **Data Visualizations**: Interactive charts with multiple chart types (bar, line, pie, doughnut).
4. **Filtering & Sorting**: Robust filtering by date range, department, status, and more.
5. **Export Options**: Export to Excel, PDF, and print-ready formats.
6. **ML Predictions**: Python-powered predictions for future PAR values, PO spending, asset depreciation, and maintenance scheduling.

## Technical Implementation

### File Structure
- `reports.js`: Main frontend JavaScript for the reports UI and interactions
- `reports_api.php`: PHP API endpoint to handle report requests
- `get_report_data.php`: PHP script to fetch report data from the database
- `DataAnalytics/get_report_analytics.py`: Python script for data analytics and predictions
- `setup_reports_tables.php`: Script to create necessary database tables

### PHP-Python Integration
The system uses PHP to bridge between the frontend and Python analytics:

1. JavaScript makes AJAX requests to the PHP endpoints
2. PHP calls the Python script with appropriate parameters
3. Python processes the data and returns JSON results
4. PHP relays the results back to the frontend

## Python ML Capabilities

The Python analytics script (`get_report_analytics.py`) provides:

1. **Data Loading**: Loads data from various sources (mock data or database)
2. **Feature Engineering**: Processes data for ML predictions
3. **Predictive Models**:
   - Linear Regression for financial forecasting
   - Random Forest for asset depreciation and maintenance predictions
4. **Model Evaluation**: Provides metrics like MAE, RMSE, R² for model performance
5. **Confidence Intervals**: Generates prediction confidence intervals

## Setup Instructions

### Prerequisites
- PHP 7.4+
- Python 3.7+
- MySQL/MariaDB database
- Required Python packages: pandas, numpy, scikit-learn

### Python Package Installation
```bash
pip install pandas numpy scikit-learn
```

### Database Setup
Run the database setup script to create necessary tables:
```
http://your-server/setup_reports_tables.php
```

### Python Script Permissions
Ensure the Python script has execute permissions:
```bash
chmod +x DataAnalytics/get_report_analytics.py
```

## Using the Reports Module

1. **Access Reports**: Click on the "Reports" section in the sidebar navigation
2. **Select Report Type**: Choose from summary, PAR detailed, PO detailed, inventory detailed, or audit logs
3. **Apply Filters**: Set date range, department, status, and other filters as needed
4. **Generate Report**: Click "Generate Report" to create the report
5. **View Visualizations**: Interact with charts by switching between chart types
6. **Export/Print**: Use the export or print buttons to export data in desired format

## ML Predictions

1. **Set Parameters**: Choose prediction type, time horizon, and confidence level
2. **Run Analysis**: Click "Run Python Analysis" to generate predictions
3. **View Results**: See predictive charts with historical data, predictions, and confidence intervals

## Troubleshooting

- If charts don't display, check browser console for JavaScript errors
- If Python integration fails, verify Python path and required packages
- For database connection issues, check database credentials and table existence

## Technical Notes

- The system falls back to mock data if database tables don't exist
- ML models are retrained with each request for demonstration purposes
- In production, consider implementing model persistence
- Time series forecasting uses a combination of historical trends and seasonal patterns 