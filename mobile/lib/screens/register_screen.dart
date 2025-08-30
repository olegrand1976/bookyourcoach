import 'package:flutter/material.dart';
import '../services/auth_service.dart';
import '../l10n/app_localizations.dart' as app_l10n;

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _password = TextEditingController();
  bool _loading = false;

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() { _loading = true; });
    final ok = await AuthService().register(
      name: _name.text.trim(),
      email: _email.text.trim(),
      password: _password.text,
    );
    setState(() { _loading = false; });
    if (!mounted) return;
    if (ok) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Compte créé')));
      Navigator.of(context).pop(true);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Échec de l\'inscription')));
    }
  }

  @override
  Widget build(BuildContext context) {
    final t = app_l10n.AppLocalizations.of(context);
    return Scaffold(
      appBar: AppBar(title: Text(t?.registerTitle ?? 'Créer un compte')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            children: [
              TextFormField(
                controller: _name,
                decoration: InputDecoration(labelText: t?.registerName ?? 'Nom complet'),
                validator: (v) => (v == null || v.trim().isEmpty) ? 'Requis' : null,
              ),
              TextFormField(
                controller: _email,
                keyboardType: TextInputType.emailAddress,
                decoration: InputDecoration(labelText: t?.loginEmail ?? 'Email'),
                validator: (v) {
                  if (v == null || v.trim().isEmpty) return 'Requis';
                  final r = RegExp(r"^[^\s@]+@[^\s@]+\.[^\s@]+$");
                  if (!r.hasMatch(v.trim())) return 'Email invalide';
                  return null;
                },
              ),
              TextFormField(
                controller: _password,
                obscureText: true,
                decoration: InputDecoration(labelText: t?.loginPassword ?? 'Mot de passe'),
                validator: (v) {
                  if (v == null || v.length < 8) return '8 caractères min.';
                  final hasNum = RegExp(r"\d").hasMatch(v);
                  final hasLetter = RegExp(r"[A-Za-z]").hasMatch(v);
                  if (!(hasNum && hasLetter)) return 'Inclure lettres et chiffres';
                  return null;
                },
              ),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: FilledButton(
                  onPressed: _loading ? null : _submit,
                  child: _loading
                      ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                      : Text(t?.registerSubmit ?? "S'inscrire"),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

