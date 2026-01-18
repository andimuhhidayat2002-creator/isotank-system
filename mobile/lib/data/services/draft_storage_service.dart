import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter/foundation.dart';

/// Service untuk menyimpan dan memuat draft inspection forms secara offline
class DraftStorageService {
  static const String _draftPrefix = 'inspection_draft_';
  static const String _draftListKey = 'inspection_draft_list';

  /// Save draft inspection form
  static Future<void> saveDraft(int jobId, Map<String, dynamic> formData) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final draftKey = '$_draftPrefix$jobId';
      
      // Save draft data
      await prefs.setString(draftKey, json.encode(formData));
      
      // Update draft list
      final draftList = prefs.getStringList(_draftListKey) ?? [];
      if (!draftList.contains(draftKey)) {
        draftList.add(draftKey);
        await prefs.setStringList(_draftListKey, draftList);
      }
      
      if (kDebugMode) {
        print('✅ Draft saved for job $jobId');
      }
    } catch (e) {
      if (kDebugMode) {
        print('❌ Error saving draft: $e');
      }
      rethrow;
    }
  }

  /// Load draft inspection form
  static Future<Map<String, dynamic>?> loadDraft(int jobId) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final draftKey = '$_draftPrefix$jobId';
      final draftJson = prefs.getString(draftKey);
      
      if (draftJson == null) return null;
      
      final draftData = json.decode(draftJson) as Map<String, dynamic>;
      
      if (kDebugMode) {
        print('✅ Draft loaded for job $jobId');
      }
      
      return draftData;
    } catch (e) {
      if (kDebugMode) {
        print('❌ Error loading draft: $e');
      }
      return null;
    }
  }

  /// Delete draft after successful submission
  static Future<void> deleteDraft(int jobId) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final draftKey = '$_draftPrefix$jobId';
      
      await prefs.remove(draftKey);
      
      // Update draft list
      final draftList = prefs.getStringList(_draftListKey) ?? [];
      draftList.remove(draftKey);
      await prefs.setStringList(_draftListKey, draftList);
      
      if (kDebugMode) {
        print('✅ Draft deleted for job $jobId');
      }
    } catch (e) {
      if (kDebugMode) {
        print('❌ Error deleting draft: $e');
      }
    }
  }

  /// Get all draft job IDs
  static Future<List<int>> getAllDraftJobIds() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final draftList = prefs.getStringList(_draftListKey) ?? [];
      
      final jobIds = draftList
          .where((key) => key.startsWith(_draftPrefix))
          .map((key) => int.tryParse(key.replaceFirst(_draftPrefix, '')))
          .where((id) => id != null)
          .cast<int>()
          .toList();
      
      return jobIds;
    } catch (e) {
      if (kDebugMode) {
        print('❌ Error getting draft list: $e');
      }
      return [];
    }
  }

  /// Check if draft exists for a job
  static Future<bool> hasDraft(int jobId) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final draftKey = '$_draftPrefix$jobId';
      return prefs.containsKey(draftKey);
    } catch (e) {
      return false;
    }
  }

  /// Clear all drafts (for cleanup)
  static Future<void> clearAllDrafts() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final draftList = prefs.getStringList(_draftListKey) ?? [];
      
      for (final key in draftList) {
        await prefs.remove(key);
      }
      
      await prefs.remove(_draftListKey);
      
      if (kDebugMode) {
        print('✅ All drafts cleared');
      }
    } catch (e) {
      if (kDebugMode) {
        print('❌ Error clearing drafts: $e');
      }
    }
  }
}

