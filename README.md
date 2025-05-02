# ICTD Inventory Management System with ML Integration

This system integrates machine learning predictions into the inventory management system to help predict maintenance needs and inventory suggestions.

## ML Prediction Features

The ML prediction system provides:

1. **Maintenance Predictions**: Identifies which items in inventory may need maintenance soon
2. **Inventory Suggestions**: Recommends inventory items that should be stocked or replaced

## Setup Instructions

### Prerequisites

- PHP server (e.g., XAMPP, WAMP, or LAMP)
- Python 3.6+ with pip
- Web browser

### Setting Up the ML Environment

1. Navigate to the DataAnalytics directory:
   ```
   cd DataAnalytics
   ```

2. Run the setup script:
   - For Linux/macOS:
     ```
     chmod +x setup.sh
     ./setup.sh
     ```
   - For Windows:
     ```
     python -m pip install -r requirements.txt
     python run_prediction.py
     ```

### Generating Predictions

The system has three ways to generate predictions:

1. **Automatic**: The ML predictions will be refreshed when users view the dashboard.
2. **Manual Refresh**: Users can click the "Refresh" button on the dashboard to update predictions.
3. **Command Line**: You can run predictions manually via command line:
   ```
   cd DataAnalytics
   python run_prediction.py
   ```

## How It Works

1. **Data Collection**:
   - Inventory data is tracked when users add or update items
   - This data includes item condition, purchase date, warranty, etc.

2. **Model Training**:
   - The Python ML script trains models based on collected data
   - For maintenance, it looks at item condition, age, and warranty
   - For inventory suggestions, it analyzes item categories and counts

3. **Prediction Generation**:
   - Models predict which items need maintenance and what inventory to stock
   - Predictions are stored in the database for display

4. **Dashboard Display**:
   - Predictions are shown in the ML Prediction Dashboard
   - Users can see maintenance priorities and inventory suggestions

## Troubleshooting

If predictions aren't showing correctly:

1. Check if Python is installed and accessible
2. Verify that required Python packages are installed (see requirements.txt)
3. Check PHP server error logs for any issues
4. Try running the prediction script manually

## For Developers

The ML integration consists of:

- PHP endpoints for data exchange:
  - `get_inventory_data.php`: Provides inventory data to Python
  - `save_maintenance_predictions.php`: Receives maintenance predictions
  - `save_inventory_suggestions.php`: Receives inventory suggestions
  - `get_maintenance_predictions.php`: Returns predictions for the dashboard
  - `get_inventory_suggestions.php`: Returns suggestions for the dashboard

- Python scripts for predictions:
  - `inventory_predictor.py`: Main ML logic and prediction engine
  - `run_prediction.py`: CLI tool for running predictions manually

If adding new types of predictions, you'll need to:
1. Update the Python models in `inventory_predictor.py`
2. Create new PHP endpoints for saving and retrieving predictions
3. Update the JavaScript code to display the new predictions 