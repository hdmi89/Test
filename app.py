from flask import Flask, jsonify, request, send_from_directory, session, redirect, render_template_string, Response
from datetime import datetime, timedelta, date
from sqlalchemy import create_engine, Column, String, Boolean, MetaData, Table
import hmac
import os
import secrets
import uuid

app = Flask(__name__, static_folder='static')

DATABASE_URL = os.environ.get('DATABASE_URL', 'sqlite:///data.db')
if DATABASE_URL.startswith('postgres://'):
    DATABASE_URL = DATABASE_URL.replace('postgres://', 'postgresql://', 1)

engine = create_engine(DATABASE_URL)
metadata = MetaData()

machines_table = Table(
    'machines', metadata,
    Column('id', String, primary_key=True),
    Column('nom', String),
    Column('numero_serie', String),
    Column('statut', String),
    Column('notes', String),
)

accessoires_table = Table(
    'accessoires', metadata,
    Column('id', String, primary_key=True),
    Column('nom', String),
    Column('notes', String),
)

locations_table = Table(
    'locations', metadata,
    Column('id', String, primary_key=True),
    Column('machine_id', String),
    Column('machine_nom', String),
    Column('machine_numero_serie', String),
    Column('client', String),
    Column('client_code_ebp', String),
    Column('client_telephone', String),
    Column('date_debut', String),
    Column('date_fin', String),
    Column('compteur_debut', String),
    Column('compteur_fin', String),
    Column('reglement_recu', Boolean),
    Column('caution_ok', Boolean),
    Column('assurance_ok', Boolean),
    Column('livraison', String),
    Column('statut_ebp', String),
    Column('referent', String),
    Column('accessoire1_id', String),
    Column('accessoire1_nom', String),
    Column('accessoire2_id', String),
    Column('accessoire2_nom', String),
    Column('accessoire3_id', String),
    Column('accessoire3_nom', String),
    Column('notes', String),
)

metadata.create_all(engine)

LIVRAISON_CHOICES = {'retrait_site', 'transporteur', 'gresiloc'}
MACHINE_STATUS_CHOICES = {'disponible', 'preparation', 'vidange', 'indisponible'}
EBP_STATUS_CHOICES = {'a_creer_ebp', 'a_facturer_ebp', 'hors_radar'}
REFERENT_CHOICES = {'robin', 'jeremie', 'sebastien'}
REFERENT_LABELS = {'robin': 'Robin', 'jeremie': 'Jérémie', 'sebastien': 'Sébastien'}

app.secret_key = os.environ.get('SECRET_KEY') or secrets.token_hex(32)
app.config['SESSION_COOKIE_HTTPONLY'] = True
app.config['SESSION_COOKIE_SAMESITE'] = 'Lax'
app.permanent_session_lifetime = timedelta(days=30)

APP_PASSWORD = os.environ.get('APP_PASSWORD')
if not APP_PASSWORD:
    APP_PASSWORD = secrets.token_urlsafe(12)
    print(f'[ATTENTION] Aucune variable APP_PASSWORD définie. Mot de passe généré pour cette instance : {APP_PASSWORD}')

CALENDAR_TOKEN = os.environ.get('CALENDAR_TOKEN')
if not CALENDAR_TOKEN:
    CALENDAR_TOKEN = secrets.token_urlsafe(16)
    print(f'[ATTENTION] Aucune variable CALENDAR_TOKEN définie. Token généré pour cette instance : {CALENDAR_TOKEN}')

LOGIN_PAGE = """
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Connexion - Parc Machine</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      background: #f8fafc;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 16px;
    }
    .login-card {
      background: #fff;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 36px 32px;
      width: 100%;
      max-width: 360px;
    }
    .brand-mark {
      width: 42px;
      height: 42px;
      border-radius: 10px;
      background: #2563eb;
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 18px;
    }
    h1 { font-size: 1.25rem; color: #0f172a; margin-bottom: 4px; }
    p.subtitle { color: #64748b; font-size: 0.88rem; margin-bottom: 24px; }
    label {
      display: block;
      font-size: 0.72rem;
      font-weight: 700;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.03em;
      margin-bottom: 6px;
    }
    input[type="password"] {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      font-size: 0.92rem;
      outline: none;
      margin-bottom: 16px;
      font-family: inherit;
      height: 40px;
    }
    input[type="password"]:focus { border-color: #2563eb; box-shadow: 0 0 0 3px #eff6ff; }
    button {
      width: 100%;
      padding: 11px;
      background: #2563eb;
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: 0.92rem;
      font-weight: 600;
      cursor: pointer;
      font-family: inherit;
    }
    button:hover { background: #1d4ed8; }
    .error {
      color: #991b1b;
      background: #fee2e2;
      border: 1px solid #fca5a5;
      border-radius: 8px;
      padding: 9px 12px;
      font-size: 0.83rem;
      margin-bottom: 16px;
    }
  </style>
</head>
<body>
  <div class="login-card">
    <div class="brand-mark">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="6" width="14" height="12"/><path d="M15 10h4l3 3v5h-7z"/><circle cx="6" cy="19" r="1.8"/><circle cx="17.5" cy="19" r="1.8"/></svg>
    </div>
    <h1>Parc Machine</h1>
    <p class="subtitle">Accès réservé à l'équipe.</p>
    {% if error %}<p class="error">{{ error }}</p>{% endif %}
    <form method="POST">
      <label for="password">Mot de passe</label>
      <input type="password" id="password" name="password" placeholder="••••••••" autofocus required />
      <button type="submit">Se connecter</button>
    </form>
  </div>
</body>
</html>
"""


def _load_table(table):
    with engine.connect() as conn:
        rows = conn.execute(table.select()).mappings().all()
        return [dict(row) for row in rows]


def _save_table(table, records):
    with engine.begin() as conn:
        conn.execute(table.delete())
        if records:
            conn.execute(table.insert(), records)


def load_machines():
    return _load_table(machines_table)


def save_machines(machines):
    _save_table(machines_table, machines)


def load_locations():
    return _load_table(locations_table)


def save_locations(locations):
    _save_table(locations_table, locations)


def load_accessoires():
    return _load_table(accessoires_table)


def save_accessoires(accessoires):
    _save_table(accessoires_table, accessoires)


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


def validate_accessoire_payload(data, partial=False):
    errors = []
    if not partial and not str(data.get('nom', '')).strip():
        errors.append("Le nom de l'accessoire est requis")
    return errors


def validate_location_payload(data, machines, accessoires, partial=False, current=None):
    errors = []

    def required(field, label):
        if not partial and not str(data.get(field, '')).strip():
            errors.append(f'{label} est requis')

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

    if 'statut_ebp' in data and data['statut_ebp'] not in EBP_STATUS_CHOICES:
        errors.append('Statut EBP invalide')

    if 'referent' in data and data['referent'] not in REFERENT_CHOICES and data['referent'] != '':
        errors.append('Référent invalide')

    accessoire_results = {}
    for slot in (1, 2, 3):
        field = f'accessoire{slot}_id'
        if field in data:
            value = data[field]
            if not value:
                accessoire_results[slot] = None
            else:
                found = next((a for a in accessoires if a['id'] == value), None)
                if found is None:
                    errors.append(f'Accessoire {slot} introuvable')
                else:
                    accessoire_results[slot] = found

    final_machine_id = data['machine_id'] if 'machine_id' in data else (current.get('machine_id', '') if current else '')
    final_accessoire_ids = []
    for slot in (1, 2, 3):
        field = f'accessoire{slot}_id'
        if field in data:
            final_accessoire_ids.append(data[field])
        else:
            final_accessoire_ids.append(current.get(field, '') if current else '')

    if not final_machine_id and not final_accessoire_ids[0]:
        errors.append('Renseignez au moins une machine ou un accessoire')

    non_empty = [v for v in final_accessoire_ids if v]
    if len(non_empty) != len(set(non_empty)):
        errors.append("Un accessoire ne peut être sélectionné qu'une seule fois")

    return errors, machine, accessoire_results


def ics_escape(text):
    return (text or '').replace('\\', '\\\\').replace(',', '\\,').replace(';', '\\;').replace('\n', '\\n')


def build_vevent(loc):
    start = loc['date_debut'].replace('-', '')
    end_date = date.fromisoformat(loc['date_fin']) + timedelta(days=1)
    end = end_date.strftime('%Y%m%d')
    accessoire1_nom = loc.get('accessoire1_nom')
    title = loc.get('machine_nom') or accessoire1_nom or 'Location'
    summary = title + (f" - {loc['client']}" if loc.get('client') else '')

    description_parts = []
    if loc.get('machine_nom'):
        description_parts.append(f"Machine : {loc['machine_nom']} (SN {loc['machine_numero_serie']})")
    if loc.get('client'):
        description_parts.append(f"Client : {loc['client']}")
    if loc.get('client_code_ebp'):
        description_parts.append(f"Code client EBP : {loc['client_code_ebp']}")
    if loc.get('client_telephone'):
        description_parts.append(f"Portable : {loc['client_telephone']}")
    if loc.get('referent'):
        description_parts.append(f"Référent : {REFERENT_LABELS.get(loc['referent'], loc['referent'])}")
    accessoires_noms = [loc.get(f'accessoire{slot}_nom') for slot in (1, 2, 3) if loc.get(f'accessoire{slot}_nom')]
    if accessoires_noms:
        description_parts.append(f"Accessoires : {', '.join(accessoires_noms)}")
    description = '\n'.join(description_parts)

    dtstamp = datetime.utcnow().strftime('%Y%m%dT%H%M%SZ')
    return '\r\n'.join([
        'BEGIN:VEVENT',
        f"UID:{loc['id']}@gestion-parc-machine",
        f"DTSTAMP:{dtstamp}",
        f"DTSTART;VALUE=DATE:{start}",
        f"DTEND;VALUE=DATE:{end}",
        f"SUMMARY:{ics_escape(summary)}",
        f"DESCRIPTION:{ics_escape(description)}",
        'END:VEVENT',
    ])


def build_ics(vevents):
    lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Gestion Parc Machine//FR', 'CALSCALE:GREGORIAN']
    lines.extend(vevents)
    lines.append('END:VCALENDAR')
    return '\r\n'.join(lines) + '\r\n'


# ---- Authentification ----

@app.before_request
def require_login():
    if request.path in ('/login', '/calendar.ics') or request.path.startswith('/static/'):
        return
    if not session.get('authenticated'):
        if request.path.startswith('/api/'):
            return jsonify({'error': 'Authentification requise'}), 401
        return redirect('/login')


@app.route('/login', methods=['GET', 'POST'])
def login():
    error = None
    if request.method == 'POST':
        password = request.form.get('password', '')
        if hmac.compare_digest(password, APP_PASSWORD):
            session.clear()
            session['authenticated'] = True
            session.permanent = True
            return redirect('/')
        error = 'Mot de passe incorrect'
    return render_template_string(LOGIN_PAGE, error=error)


@app.route('/logout')
def logout():
    session.clear()
    return redirect('/login')


@app.route('/')
def index():
    return send_from_directory('static', 'index.html')


@app.route('/api/calendar-feed-url')
def calendar_feed_url():
    return jsonify({'url': f"{request.url_root}calendar.ics?token={CALENDAR_TOKEN}"})


@app.route('/calendar.ics')
def calendar_feed():
    token = request.args.get('token', '')
    if not hmac.compare_digest(token, CALENDAR_TOKEN):
        return jsonify({'error': 'Token invalide'}), 403
    locations = load_locations()
    ics = build_ics([build_vevent(l) for l in locations])
    return Response(ics, mimetype='text/calendar', headers={
        'Content-Disposition': 'inline; filename="parc-machine.ics"'
    })


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


# ---- Accessoires ----

@app.route('/api/accessoires', methods=['GET'])
def get_accessoires():
    accessoires = load_accessoires()
    accessoires.sort(key=lambda a: a.get('nom', '').lower())
    return jsonify(accessoires)


@app.route('/api/accessoires', methods=['POST'])
def create_accessoire():
    data = request.get_json() or {}
    errors = validate_accessoire_payload(data)
    if errors:
        return jsonify({'error': '; '.join(errors)}), 400

    accessoire = {
        'id': str(uuid.uuid4()),
        'nom': data['nom'].strip(),
        'notes': data.get('notes', '').strip(),
    }
    accessoires = load_accessoires()
    accessoires.append(accessoire)
    save_accessoires(accessoires)
    return jsonify(accessoire), 201


@app.route('/api/accessoires/<accessoire_id>', methods=['PATCH'])
def update_accessoire(accessoire_id):
    accessoires = load_accessoires()
    accessoire = next((a for a in accessoires if a['id'] == accessoire_id), None)
    if accessoire is None:
        return jsonify({'error': 'Not found'}), 404

    data = request.get_json() or {}
    errors = validate_accessoire_payload(data, partial=True)
    if errors:
        return jsonify({'error': '; '.join(errors)}), 400

    if 'nom' in data and data['nom'].strip():
        accessoire['nom'] = data['nom'].strip()
    if 'notes' in data:
        accessoire['notes'] = data['notes'].strip()

    save_accessoires(accessoires)
    return jsonify(accessoire)


@app.route('/api/accessoires/<accessoire_id>', methods=['DELETE'])
def delete_accessoire(accessoire_id):
    accessoires = load_accessoires()
    accessoires = [a for a in accessoires if a['id'] != accessoire_id]
    save_accessoires(accessoires)
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
    accessoires = load_accessoires()
    errors, machine, accessoire_results = validate_location_payload(data, machines, accessoires)
    if errors:
        return jsonify({'error': '; '.join(errors)}), 400

    location = {
        'id': str(uuid.uuid4()),
        'machine_id': machine['id'] if machine else '',
        'machine_nom': machine['nom'] if machine else '',
        'machine_numero_serie': machine['numero_serie'] if machine else '',
        'client': data.get('client', '').strip(),
        'client_code_ebp': data.get('client_code_ebp', '').strip(),
        'client_telephone': data.get('client_telephone', '').strip(),
        'date_debut': data['date_debut'],
        'date_fin': data['date_fin'],
        'compteur_debut': str(data.get('compteur_debut', '')).strip(),
        'compteur_fin': str(data.get('compteur_fin', '')).strip(),
        'reglement_recu': bool(data.get('reglement_recu', False)),
        'caution_ok': bool(data.get('caution_ok', False)),
        'assurance_ok': bool(data.get('assurance_ok', False)),
        'livraison': data.get('livraison') if data.get('livraison') in LIVRAISON_CHOICES else 'retrait_site',
        'statut_ebp': data.get('statut_ebp') if data.get('statut_ebp') in EBP_STATUS_CHOICES else 'a_creer_ebp',
        'referent': data.get('referent') if data.get('referent') in REFERENT_CHOICES else '',
        'notes': data.get('notes', '').strip(),
    }
    for slot in (1, 2, 3):
        found = accessoire_results.get(slot)
        location[f'accessoire{slot}_id'] = found['id'] if found else ''
        location[f'accessoire{slot}_nom'] = found['nom'] if found else ''

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
    accessoires = load_accessoires()
    data = request.get_json() or {}
    errors, machine, accessoire_results = validate_location_payload(data, machines, accessoires, partial=True, current=location)
    if errors:
        return jsonify({'error': '; '.join(errors)}), 400

    if 'machine_id' in data:
        location['machine_id'] = machine['id'] if machine else ''
        location['machine_nom'] = machine['nom'] if machine else ''
        location['machine_numero_serie'] = machine['numero_serie'] if machine else ''
    for slot, found in accessoire_results.items():
        location[f'accessoire{slot}_id'] = found['id'] if found else ''
        location[f'accessoire{slot}_nom'] = found['nom'] if found else ''
    if 'client' in data:
        location['client'] = data['client'].strip()
    if 'client_code_ebp' in data:
        location['client_code_ebp'] = data['client_code_ebp'].strip()
    if 'client_telephone' in data:
        location['client_telephone'] = data['client_telephone'].strip()
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
    if 'statut_ebp' in data and data['statut_ebp'] in EBP_STATUS_CHOICES:
        location['statut_ebp'] = data['statut_ebp']
    if 'referent' in data:
        location['referent'] = data['referent'] if data['referent'] in REFERENT_CHOICES else ''
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


@app.route('/api/locations/<location_id>/ics')
def location_ics(location_id):
    locations = load_locations()
    loc = next((l for l in locations if l['id'] == location_id), None)
    if loc is None:
        return jsonify({'error': 'Not found'}), 404
    ics = build_ics([build_vevent(loc)])
    return Response(ics, mimetype='text/calendar', headers={
        'Content-Disposition': f'attachment; filename="location-{location_id}.ics"'
    })


if __name__ == '__main__':
    os.makedirs('static', exist_ok=True)
    app.run(debug=True, port=5000)
