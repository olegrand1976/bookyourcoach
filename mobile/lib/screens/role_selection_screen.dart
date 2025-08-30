import 'package:flutter/material.dart';
import '../l10n/app_localizations.dart' as app_l10n;

class RoleSelectionScreen extends StatefulWidget {
  const RoleSelectionScreen({super.key});

  @override
  State<RoleSelectionScreen> createState() => _RoleSelectionScreenState();
}

class _RoleSelectionScreenState extends State<RoleSelectionScreen> {
  bool asStudent = false;
  bool asTeacher = false;

  @override
  Widget build(BuildContext context) {
    final t = app_l10n.AppLocalizations.of(context);
    return Scaffold(
      appBar: AppBar(title: Text(t?.roleTitle ?? 'Sélection du rôle')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(t?.roleChoose ?? 'Choisissez votre rôle:'),
            CheckboxListTile(
              title: Text(t?.roleStudent ?? 'Élève'),
              value: asStudent,
              onChanged: (v) => setState(() => asStudent = v ?? false),
            ),
            CheckboxListTile(
              title: Text(t?.roleTeacher ?? 'Enseignant'),
              value: asTeacher,
              onChanged: (v) => setState(() => asTeacher = v ?? false),
            ),
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: (!asStudent && !asTeacher) ? null : () {
                  Navigator.of(context).pop({'student': asStudent, 'teacher': asTeacher});
                },
                child: Text(t?.commonContinue ?? 'Continuer'),
              ),
            )
          ],
        ),
      ),
    );
  }
}