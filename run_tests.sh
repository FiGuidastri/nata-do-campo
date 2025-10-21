#!/bin/bash

echo "=== Iniciando testes do sistema ==="
echo

echo "1. Testando banco de dados..."
php tests/test_database.php
echo

echo "2. Testando autenticação..."
php tests/test_auth.php
echo

echo "3. Testando API..."
php tests/test_api.php
echo

echo "4. Testando estoque..."
php tests/test_estoque.php
echo

echo "=== Testes concluídos ==="