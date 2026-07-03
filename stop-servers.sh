#!/bin/bash

# Get the directory of the script
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PID_FILE="$SCRIPT_DIR/.servers.pid"

if [ ! -f "$PID_FILE" ]; then
    echo "No hay registro de servidores activos (archivo .servers.pid no encontrado)."
    
    # Check if anything is still running on port 5173 anyway
    if command -v lsof >/dev/null 2>&1; then
        PORT_PID=$(lsof -t -i:5173 2>/dev/null)
        if [ -n "$PORT_PID" ]; then
            echo "Se encontró un proceso en el puerto 5173 (PID: $PORT_PID). Deteniendo..."
            kill "$PORT_PID" 2>/dev/null || kill -9 "$PORT_PID" 2>/dev/null || true
            echo "Proceso detenido."
        fi
    fi
    exit 0
fi

PID=$(cat "$PID_FILE")

if ps -p "$PID" > /dev/null 2>&1; then
    echo "Deteniendo dev server (PID: $PID)..."
    # Kill process descendants
    pkill -P "$PID" 2>/dev/null || true
    kill "$PID" 2>/dev/null || true
    
    # Wait a bit
    sleep 1
    
    if ps -p "$PID" > /dev/null 2>&1; then
        echo "Forzando detención (SIGKILL)..."
        pkill -9 -P "$PID" 2>/dev/null || true
        kill -9 "$PID" 2>/dev/null || true
    fi
    echo "Servidores detenidos correctamente."
else
    echo "Los servidores ya estaban detenidos (el proceso con PID $PID no existe)."
fi

# Clean up port 5173 just in case of orphans
if command -v lsof >/dev/null 2>&1; then
    PORT_PID=$(lsof -t -i:5173 2>/dev/null)
    if [ -n "$PORT_PID" ]; then
        kill "$PORT_PID" 2>/dev/null || kill -9 "$PORT_PID" 2>/dev/null || true
    fi
elif command -v fuser >/dev/null 2>&1; then
    fuser -k 5173/tcp 2>/dev/null || true
fi

rm -f "$PID_FILE"
