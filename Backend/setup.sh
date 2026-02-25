#!/bin/bash

echo "Installing Backend Dependencies..."
cd backend
npm install

echo "Installing Frontend Dependencies..."
cd ../frontend
npm install

echo "Setup complete!"
echo "To start the project:"
echo "1. Backend: cd backend && node server.js"
echo "2. Frontend: cd frontend && npm run dev"
