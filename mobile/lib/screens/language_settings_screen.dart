import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../state/app_state.dart';

class LanguageSettingsScreen extends StatefulWidget {
  const LanguageSettingsScreen({super.key});

  @override
  State<LanguageSettingsScreen> createState() => _LanguageSettingsScreenState();
}

class _LanguageSettingsScreenState extends State<LanguageSettingsScreen> {
  late Locale _locale;

  @override
  void initState() {
    super.initState();
    _locale = context.read<AppState>().locale;
  }

  Future<void> _setLocale(Locale locale) async {
    setState(() { _locale = locale; });
    await context.read<AppState>().setLocale(locale);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Langue')),
      body: ListView(
        children: [
          RadioListTile<Locale>(
            title: const Text('Français'),
            value: const Locale('fr'),
            groupValue: _locale,
            onChanged: (v) => _setLocale(v!),
          ),
          RadioListTile<Locale>(
            title: const Text('English'),
            value: const Locale('en'),
            groupValue: _locale,
            onChanged: (v) => _setLocale(v!),
          ),
        ],
      ),
    );
  }
}

