from flask import Flask, jsonify, request, send_from_directory
import json
import os
import uuid

app = Flask(__name__, static_folder='static')

DATA_FILE = 'locations.json'

LIVRAISON_CHOICES = {'retrait_site', 'transporteur', 'gresiloc'}


def load_locations():
    if not os.path.exists(DATA_FILE):
        return []
    with open(DATA_FILE, 'r') as f:
        return json.load(f)


def save_locations(locations):
    with open(DATA_FILE, 'w') as f:
        json.dump(locations, f, indent=2)


def validate_payload(data, partial=False):
    errors = []

    def required(field, label):
        if not partial and not str(data.get(field, '')).strip():
            errors.append(f'{label} est requis')

    required('machine', 'La machine')
    required('date_debut', 'La date de début')
    required('date_fin', 'La date de fin')

    if 'date_debut' in data and 'date_fin' in data:
        if data.get('date_debut') and data.get('date_fin') and data['date_fin'] < data['date_debut']:
            errors.append('La date de fin doit être postérieure à la date de début')

    if 'livraison' in data and data['livraison'] not in LIVRAISON_CHOICES:
        errors.append('Mode de livraison invalide')

    return errors


@app.route('/')
def index():
    return send_from_directory('static', 'index.html')


@app.route('/api/locations', methods=['GET'])
def get_locations():
    locations = load_locations()
    locations.sort(key=lambda l: l.get('date_debut', ''))
    return jsonify(locations)


@app.route('/api/locations', methods=['POST'])
def create_location():
    data = request.get_json() or {}
    errors = validate_payload(data)
    if errors:
        return jsonify({'error': '; '.join(errors)}), 400

    location = {
        'id': str(uuid.uuid4()),
        'machine': data['machine'].strip(),
        'client': data.get('client', '').strip(),
        'date_debut': data['date_debut'],
        'date_fin': data['date_fin'],
        'reglement_recu': bool(data.get('reglement_recu', False)),
        'caution_ok': bool(data.get('caution_ok', False)),
        'assurance_ok': bool(data.get('assurance_ok', False)),
        'livraison': data.get('livraison') if data.get('livraison') in LIVRAISON_CHOICES else 'retrait_site',
        'notes': data.get('notes', '').strip(),
    }
    locations = load_locations()
    locations.append(location)
    save_locations(locations)
    return jsonify(location), 201


@app.route('/api/locations/<location_id>', methods=['PATCH'])
def update_location(location_id):
    locations = load_locations()
    location = next((l for l in locations if l['id'] == location_id), None)
    if location is None:
        return jsonify({'error': 'Not found'}), 404

    data = request.get_json() or {}
    errors = validate_payload(data, partial=True)
    if errors:
        return jsonify({'error': '; '.join(errors)}), 400

    if 'machine' in data and data['machine'].strip():
        location['machine'] = data['machine'].strip()
    if 'client' in data:
        location['client'] = data['client'].strip()
    if 'date_debut' in data and data['date_debut']:
        location['date_debut'] = data['date_debut']
    if 'date_fin' in data and data['date_fin']:
        location['date_fin'] = data['date_fin']
    if 'reglement_recu' in data:
        location['reglement_recu'] = bool(data['reglement_recu'])
    if 'caution_ok' in data:
        location['caution_ok'] = bool(data['caution_ok'])
    if 'assurance_ok' in data:
        location['assurance_ok'] = bool(data['assurance_ok'])
    if 'livraison' in data and data['livraison'] in LIVRAISON_CHOICES:
        location['livraison'] = data['livraison']
    if 'notes' in data:
        location['notes'] = data['notes'].strip()

    save_locations(locations)
    return jsonify(location)


@app.route('/api/locations/<location_id>', methods=['DELETE'])
def delete_location(location_id):
    locations = load_locations()
    locations = [l for l in locations if l['id'] != location_id]
    save_locations(locations)
    return '', 204


if __name__ == '__main__':
    os.makedirs('static', exist_ok=True)
    app.run(debug=True, port=5000)
