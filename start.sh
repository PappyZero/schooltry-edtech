#!/bin/bash

# Start the queue worker in the background
php artisan queue:work --tries=1 --timeout=300 > storage/logs/queue.log 2>&1 &


# Store the queue worker process ID
QUEUE_WORKER_PID=$!

# Function to clean up processes on script exit
cleanup() {
    echo "Stopping processes..."
    # Kill the queue worker
    kill $QUEUE_WORKER_PID 2>/dev/null
    # Kill any remaining PHP processes
    pkill -f "php artisan serve" 2>/dev/null
    exit 0
}

# Trap script termination
trap cleanup INT TERM

# Start the Laravel development server
echo "Starting Laravel development server..."
php artisan serve --host=0.0.0.0 &

# Store the server process ID
SERVER_PID=$!

echo "Development server and queue worker started."
echo "Press Ctrl+C to stop both processes."

# Wait for the server process to complete
wait $SERVER_PID

# If we get here, the server was stopped
cleanup
