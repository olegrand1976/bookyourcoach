import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/auth_service.dart';
import '../services/profile_service.dart';
import '../services/api_client.dart';
import '../state/app_state.dart';
import '../routes.dart';
import 'register_screen.dart';
import 'forgot_password_screen.dart';
import 'role_selection_screen.dart';
import '../l10n/app_localizations.dart' as app_l10n;

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailCtrl = TextEditingController();
  final _passCtrl = TextEditingController();
  bool _loading = false;
  String? _error;

  @override
  void dispose() {
    _emailCtrl.dispose();
    _passCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() { _loading = true; _error = null; });
    final auth = AuthService();
    final ok = await auth.login(_emailCtrl.text.trim(), _passCtrl.text);
    if (!mounted) return;
    if (!ok) {
      setState(() { _loading = false; _error = 'Identifiants invalides'; });
      return;
    }
    // Récupérer le profil et router
    final client = await ApiFactory.authed();
    final profileService = ProfileService(client);
    try {
      var me = await profileService.fetchMe();
      if (!mounted) return;
      context.read<AppState>().setMe(me);
      context.read<AppState>().setToken('set');
      // Si aucun rôle, ouvrir l'assistant de sélection
      if (!(me.isStudent || me.isTeacher)) {
        final result = await Navigator.of(context).push<Map<String, bool>>(
          MaterialPageRoute(builder: (_) => const RoleSelectionScreen()),
        );
        if (result != null) {
          await profileService.initRoles(
            asStudent: result['student'] == true,
            asTeacher: result['teacher'] == true,
          );
        }
        // rafraîchir me
        me = await profileService.fetchMe();
        if (!mounted) return;
        context.read<AppState>().setMe(me);
      }
      Navigator.of(context).pushReplacementNamed(AppRoutes.home);
    } catch (_) {
      setState(() { _error = 'Erreur de récupération du profil'; });
    } finally {
      if (mounted) setState(() { _loading = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    final t = app_l10n.AppLocalizations.of(context);
    return Scaffold(
      appBar: AppBar(title: Text(t?.loginTitle ?? 'Connexion')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (_error != null) ...[
                Text(_error!, style: const TextStyle(color: Colors.red)),
                const SizedBox(height: 8),
              ],
              TextFormField(
                controller: _emailCtrl,
                decoration: InputDecoration(labelText: t?.loginEmail ?? 'Email'),
                validator: (v) => (v==null || v.isEmpty) ? 'Email requis' : null,
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _passCtrl,
                decoration: InputDecoration(labelText: t?.loginPassword ?? 'Mot de passe'),
                obscureText: true,
                validator: (v) => (v==null || v.length<6) ? 'Mot de passe invalide' : null,
              ),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _loading ? null : _submit,
                  child: _loading ? const CircularProgressIndicator() : Text(t?.loginSubmit ?? 'Se connecter'),
                ),
              ),
              const SizedBox(height: 16),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  TextButton(
                    onPressed: _loading ? null : () {
                      Navigator.of(context).push(
                        MaterialPageRoute(builder: (_) => const RegisterScreen()),
                      );
                    },
                    child: Text(t?.loginRegister ?? "S'inscrire"),
                  ),
                  TextButton(
                    onPressed: _loading ? null : () {
                      Navigator.of(context).push(
                        MaterialPageRoute(builder: (_) => const ForgotPasswordScreen()),
                      );
                    },
                    child: Text(t?.loginForgot ?? 'Mot de passe oublié ?'),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}