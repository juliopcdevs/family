db = db.getSiblingDB('family_hub');

db.createUser({
  user: 'family_admin',
  pwd: 'local_password',
  roles: [
    {
      role: 'readWrite',
      db: 'family_hub'
    }
  ]
});
