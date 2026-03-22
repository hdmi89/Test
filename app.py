from flask import Flask, jsonify, request, send_from_directory
import json
import os
import uuid

app = Flask(__name__, static_folder='static')

DATA_FILE = 'todos.json'


def load_todos():
    if not os.path.exists(DATA_FILE):
        return []
    with open(DATA_FILE, 'r') as f:
        return json.load(f)


def save_todos(todos):
    with open(DATA_FILE, 'w') as f:
        json.dump(todos, f, indent=2)


@app.route('/')
def index():
    return send_from_directory('static', 'index.html')


@app.route('/api/todos', methods=['GET'])
def get_todos():
    return jsonify(load_todos())


@app.route('/api/todos', methods=['POST'])
def create_todo():
    data = request.get_json()
    if not data or not data.get('title', '').strip():
        return jsonify({'error': 'Title is required'}), 400
    todos = load_todos()
    todo = {
        'id': str(uuid.uuid4()),
        'title': data['title'].strip(),
        'completed': False,
    }
    todos.append(todo)
    save_todos(todos)
    return jsonify(todo), 201


@app.route('/api/todos/<todo_id>', methods=['PATCH'])
def update_todo(todo_id):
    todos = load_todos()
    todo = next((t for t in todos if t['id'] == todo_id), None)
    if todo is None:
        return jsonify({'error': 'Not found'}), 404
    data = request.get_json() or {}
    if 'completed' in data:
        todo['completed'] = bool(data['completed'])
    if 'title' in data and data['title'].strip():
        todo['title'] = data['title'].strip()
    save_todos(todos)
    return jsonify(todo)


@app.route('/api/todos/<todo_id>', methods=['DELETE'])
def delete_todo(todo_id):
    todos = load_todos()
    todos = [t for t in todos if t['id'] != todo_id]
    save_todos(todos)
    return '', 204


if __name__ == '__main__':
    os.makedirs('static', exist_ok=True)
    app.run(debug=True, port=5000)
