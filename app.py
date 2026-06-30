from flask import Flask, jsonify, request, send_from_directory
import json
import os
import uuid

app = Flask(__name__, static_folder='static')

MACHINES_FILE = 'machines.json'
LOCATIONS_FILE = 'locations.json'

LIVRAISON_CHOICES = {'retrait_site', 'transporteur', 'gresiloc'}
MACHINE_STATUS_CHOICES = {'disponible', 'preparation', 'vidange', 'indisponible'}


def load_json(path):
    if not os.path.exists(path):
        return []
    with open(path, 'r') as f:
        return json.load(f)


def save_json(path, data):
    with open(path, 'w') as f:
        json.dump(data, f, indent=2)


def load_machines():
    return load_json(MACHINES_FILE)


def save_machines(machines):
    save_json(MACHINES_FILE, machines)


def load_locations():
    return load_json(LOCATIONS_FILE)


def save_locations(locations):
    save_json(LOCATIONS_FILE, locations)


def validate_machine_payload(data, machines, machine_id=None, partial=False):
    errors = []

    def required(field, label):
        if not partial and not str(data.get(field, '')).strip():
            errors.append(f'{label} est requis')

    required('nom', 'Le nom de la machine')
    required('numero_serie', "Le numéro de série")

    numero_serie = str(data.get('numero_serie', '')).strip()
    if numero_serie:
        duplicate = next((m for m in machines if m['numero_serie'] == numero_serie and m['id'] != machine_id), None)
        if duplicate:
            errors.append('Ce numéro de série est déjà utilisé')

    if 'statut' in data and data['statut'] not in MACHINE_STATUS_CHOICES:
        errors.append('Statut invalide')

    return errors


def validate_location_payload(data, machines, partial=False):
    errors = []

    def required(field, label):
        if not partial and not str(data.get(field, '')).strip():
            errors.append(f'{label} est requis')

    required('machine_id', 'La machine')
    required('date_debut', 'La date de début')
    required('date_fin', 'La date de fin')

    machine = None
    if data.get('machine_id'):
        machine = next((m for m in machines if m['id'] == data['machine_id']), None)
        if machine is None:
            errors.append('Machine introuvable')

    if 'date_debut' in data and 'date_fin' in data:
        if data.get('date_debut') and data.get('date_fin') and data['date_fin'] < data['date_debut']:
            errors.append('La date de fin doit être postérieure à la date de début')

    if 'livraison' in data and data['livraison'] not in LIVRAISON_CHOICES:
        errors.append('Mode de livraison invalide')

    return errors, machine


@app.route('/')
def index():
    return send_from_directory('static', 'index.html')


# ---- Machines (parc) ----

@app.route('/api/machines', methods=['GET'])
def get_machines():
    machines = load_machines()
    machines.sort(key=lambda m: m.get('nom', '').lower())
    return jsonify(machines)


@app.route('/api/machines', methods=['POST'])
def create_machine():
    data = request.get_json() or {}
    machines = load_machines()
    errors = validate_machine_payload(data, machines)
    if errors:
        return jsonify({'error': '; '.join(errors)}), 400

    machine = {
        'id': str(uuid.uuid4()),
        'nom': data['nom'].strip(),
        'numero_serie': data['numero_serie'].strip(),
        'statut': data.get('statut') if data.get('statut') in MACHINE_STATUS_CHOICES else 'disponible',
        'notes': data.get('notes', '').strip(),
    }
    machines.append(machine)
    save_machines(machines)
    return jsonify(machine), 201


@app.route('/api/machines/<machine_id>', methods=['PATCH'])
def update_machine(machine_id):
    machines = load_machines()
    machine = next((m for m in machines if m['id'] == machine_id), None)
    if machine is None:
        return jsonify({'error': 'Not found'}), 404

    data = request.get_json() or {}
    errors = validate_machine_payload(data, machines, machine_id=machine_id, partial=True)
    if errors:
        return jsonify({'error': '; '.join(errors)}), 400

    if 'nom' in data and data['nom'].strip():
        machine['nom'] = data['nom'].strip()
    if 'numero_serie' in data and data['numero_serie'].strip():
        machine['numero_serie'] = data['numero_serie'].strip()
    if 'statut' in data and data['statut'] in MACHINE_STATUS_CHOICES:
        machine['statut'] = data['statut']
    if 'notes' in data:
        machine['notes'] = data['notes'].strip()

    save_machines(machines)
    return jsonify(machine)


@app.route('/api/machines/<machine_id>', methods=['DELETE'])
def delete_machine(machine_id):
    machines = load_machines()
    machines = [m for m in machines if m['id'] != machine_id]
    save_machines(machines)
    return '', 204


# ---- Locations ----

@app.route('/api/locations', methods=['GET'])
def get_locations():
    locations = load_locations()
    locations.sort(key=lambda l: l.get('date_debut', ''))
    return jsonify(locations)


@app.route('/api/locations', methods=['POST'])
def create_location():
    data = request.get_json() or {}
    machines = load_machines()
    errors, machine = validate_location_payload(data, machines)
    if errors:
        return jsonify({'error': '; '.join(errors)}), 400

    location = {
        'id': str(uuid.uuid4()),
        'machine_id': machine['id'],
        'machine_nom': machine['nom'],
        'machine_numero_serie': machine['numero_serie'],
        'client': data.get('client', '').strip(),
        'date_debut': data['date_debut'],
        'date_fin': data['date_fin'],
        'compteur_debut': str(data.get('compteur_debut', '')).strip(),
        'compteur_fin': str(data.get('compteur_fin', '')).strip(),
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

    machines = load_machines()
    data = request.get_json() or {}
    errors, machine = validate_location_payload(data, machines, partial=True)
    if errors:
        return jsonify({'error': '; '.join(errors)}), 400

    if machine is not None:
        location['machine_id'] = machine['id']
        location['machine_nom'] = machine['nom']
        location['machine_numero_serie'] = machine['numero_serie']
    if 'client' in data:
        location['client'] = data['client'].strip()
    if 'date_debut' in data and data['date_debut']:
        location['date_debut'] = data['date_debut']
    if 'date_fin' in data and data['date_fin']:
        location['date_fin'] = data['date_fin']
    if 'compteur_debut' in data:
        location['compteur_debut'] = str(data['compteur_debut']).strip()
    if 'compteur_fin' in data:
        location['compteur_fin'] = str(data['compteur_fin']).strip()
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
