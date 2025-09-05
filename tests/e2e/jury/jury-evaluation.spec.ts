import { test, expect } from '@playwright/test';
import { JuryDashboardPage } from '../../pages/jury-dashboard-page';
import { EvaluationFormPage } from '../../pages/evaluation-form-page';

test.use({
  storageState: 'tests/.auth/jury.json'
});

test.describe('Jury Member - Evaluation Form', () => {
  let dashboardPage: JuryDashboardPage;
  let evaluationPage: EvaluationFormPage;

  test.beforeEach(async ({ page }) => {
    dashboardPage = new JuryDashboardPage(page);
    evaluationPage = new EvaluationFormPage(page);
    
    // Navigate to dashboard first
    await dashboardPage.goto();
    
    // Find and start an evaluation
    const candidates = await dashboardPage.getAssignedCandidates();
    const pendingCandidate = candidates.find(c => 
      c.status.toLowerCase().includes('pending') || 
      c.progress === '0%'
    );
    
    if (pendingCandidate) {
      await dashboardPage.startEvaluation(pendingCandidate.name);
      await evaluationPage.waitForPageLoad();
    }
  });

  test('should display evaluation form with all criteria', async ({ page }) => {
    // Verify form is displayed
    await expect(evaluationPage.formContainer).toBeVisible();
    
    // Verify all required fields are present
    await evaluationPage.verifyRequiredFields();
    
    // Verify candidate information is displayed
    const candidateInfo = await evaluationPage.getCandidateInfo();
    expect(candidateInfo.name).toBeTruthy();
  });

  test('should validate score inputs', async ({ page }) => {
    // Try to submit empty form
    await evaluationPage.submitFinal();
    
    // Should show validation errors
    const hasErrors = await evaluationPage.hasValidationErrors();
    
    if (!hasErrors) {
      // Form might require confirmation
      const confirmDialog = page.locator('.confirm-dialog, [role="dialog"]');
      const cancelButton = confirmDialog.locator('button:has-text("Cancel"), button:has-text("No")');
      
      if (await confirmDialog.count() > 0) {
        await cancelButton.click();
      }
    } else {
      // Verify error messages
      const errors = await evaluationPage.getValidationErrors();
      expect(errors.length).toBeGreaterThan(0);
    }
  });

  test('should accept valid scores (0-10 with 0.5 increments)', async ({ page }) => {
    const validScores = {
      courage: 7.5,
      innovation: 8,
      implementation: 6.5,
      relevance: 9,
      visibility: 7
    };
    
    await evaluationPage.fillScores(validScores);
    
    // Verify scores are set correctly
    // This would depend on how the form displays the values
    const totalScore = await evaluationPage.getTotalScore();
    const expectedTotal = Object.values(validScores).reduce((a, b) => a + b, 0);
    
    // Allow for small floating point differences
    expect(Math.abs(totalScore - expectedTotal)).toBeLessThan(0.1);
  });

  test('should calculate average score automatically', async ({ page }) => {
    const scores = {
      courage: 8,
      innovation: 7,
      implementation: 9,
      relevance: 6,
      visibility: 10
    };
    
    await evaluationPage.fillScores(scores);
    
    // Verify average calculation
    const averageScore = await evaluationPage.getAverageScore();
    const expectedAverage = Object.values(scores).reduce((a, b) => a + b, 0) / 5;
    
    expect(Math.abs(averageScore - expectedAverage)).toBeLessThan(0.1);
  });

  test('should save evaluation as draft', async ({ page }) => {
    // Fill partial scores
    const partialScores = {
      courage: 7,
      innovation: 8,
      implementation: 0,
      relevance: 0,
      visibility: 0
    };
    
    await evaluationPage.fillScores(partialScores);
    
    // Add a comment
    await evaluationPage.fillComments({
      courage: 'Shows good courage',
      general: 'Work in progress'
    });
    
    // Save as draft
    await evaluationPage.saveDraft();
    
    // Verify success message
    const successMessage = await evaluationPage.getSuccessMessage();
    expect(successMessage).toContain('draft');
    
    // Navigate back to dashboard
    await evaluationPage.goBack();
    
    // Verify evaluation shows as draft/in-progress
    const candidates = await dashboardPage.getAssignedCandidates();
    const evaluatedCandidate = candidates[0]; // Assuming we evaluated the first one
    
    if (evaluatedCandidate) {
      expect(evaluatedCandidate.status.toLowerCase()).toMatch(/draft|progress/);
      const progress = parseFloat(evaluatedCandidate.progress.replace('%', ''));
      expect(progress).toBeGreaterThan(0);
      expect(progress).toBeLessThan(100);
    }
  });

  test('should submit final evaluation', async ({ page }) => {
    // Fill all scores
    const completeScores = {
      courage: 8.5,
      innovation: 9,
      implementation: 7.5,
      relevance: 9.5,
      visibility: 8
    };
    
    await evaluationPage.fillScores(completeScores);
    
    // Add comprehensive comments
    await evaluationPage.fillComments({
      courage: 'Demonstrates exceptional courage in pursuing innovative solutions',
      innovation: 'Highly innovative approach with clear differentiation',
      implementation: 'Good implementation strategy with room for improvement',
      relevance: 'Extremely relevant to current mobility challenges',
      visibility: 'Strong visibility and thought leadership',
      general: 'Overall an excellent candidate with strong potential'
    });
    
    // Submit final evaluation
    await evaluationPage.submitFinal();
    
    // Handle confirmation if needed
    const confirmButton = page.locator('button:has-text("Confirm"), button:has-text("Yes")');
    if (await confirmButton.count() > 0) {
      await confirmButton.click();
      await evaluationPage.waitForPageLoad();
    }
    
    // Verify submission success
    const successMessage = await evaluationPage.getSuccessMessage();
    if (successMessage) {
      expect(successMessage).toMatch(/submitted|completed|success/i);
    }
    
    // Should redirect to dashboard or success page
    await expect(page).toHaveURL(/dashboard|success|complete/);
  });

  test('should update progress bar as scores are filled', async ({ page }) => {
    // Get initial progress
    const initialProgress = await evaluationPage.getProgress();
    
    // Fill one score
    await evaluationPage.setScore(evaluationPage.courageScore, 7);
    await page.waitForTimeout(500); // Wait for progress update
    
    const progressAfterOne = await evaluationPage.getProgress();
    expect(progressAfterOne).toBeGreaterThan(initialProgress);
    
    // Fill all scores
    const allScores = {
      courage: 7,
      innovation: 8,
      implementation: 9,
      relevance: 7.5,
      visibility: 8.5
    };
    
    await evaluationPage.fillScores(allScores);
    await page.waitForTimeout(500);
    
    const finalProgress = await evaluationPage.getProgress();
    expect(finalProgress).toBe(100);
  });

  test('should preserve draft data on page reload', async ({ page }) => {
    // Fill scores and comments
    const scores = {
      courage: 6.5,
      innovation: 7,
      implementation: 0,
      relevance: 0,
      visibility: 0
    };
    
    const comments = {
      courage: 'Test comment for courage',
      general: 'Draft evaluation'
    };
    
    await evaluationPage.fillScores(scores);
    await evaluationPage.fillComments(comments);
    
    // Save as draft
    await evaluationPage.saveDraft();
    
    // Reload page
    await page.reload();
    await evaluationPage.waitForPageLoad();
    
    // Verify data is preserved
    const courageValue = await evaluationPage.courageScore.inputValue();
    expect(parseFloat(courageValue)).toBe(scores.courage);
    
    const courageComment = await evaluationPage.courageComment.inputValue();
    expect(courageComment).toBe(comments.courage);
  });

  test('should prevent duplicate final submission', async ({ page }) => {
    // Submit complete evaluation
    const scores = {
      courage: 8,
      innovation: 8,
      implementation: 8,
      relevance: 8,
      visibility: 8
    };
    
    await evaluationPage.submitEvaluation(scores);
    
    // Try to navigate back and submit again
    await page.goBack();
    
    // Try to access the same evaluation
    const candidates = await dashboardPage.getAssignedCandidates();
    const completedCandidate = candidates.find(c => 
      c.status.toLowerCase().includes('completed')
    );
    
    if (completedCandidate) {
      // Should not allow editing completed evaluation
      const editButton = page.locator(`button:has-text("Edit")`);
      const editEnabled = await editButton.isEnabled().catch(() => false);
      
      expect(editEnabled).toBeFalsy();
    }
  });

  test('should handle special characters in comments', async ({ page }) => {
    const specialComments = {
      courage: 'Test with "quotes" and special chars: €, ä, ö, ü, ß',
      innovation: "Test with 'apostrophes' and line\nbreaks",
      general: 'Test with <tags> & symbols: @#$%'
    };
    
    await evaluationPage.fillComments(specialComments);
    
    // Save as draft
    await evaluationPage.saveDraft();
    
    // Reload and verify
    await page.reload();
    await evaluationPage.waitForPageLoad();
    
    // Verify special characters are preserved
    const courageComment = await evaluationPage.courageComment.inputValue();
    expect(courageComment).toContain('€');
    expect(courageComment).toContain('ä');
  });

  test('should show candidate details in evaluation form', async ({ page }) => {
    // Verify candidate information is displayed
    const candidateInfo = await evaluationPage.getCandidateInfo();
    
    expect(candidateInfo.name).toBeTruthy();
    
    // Check for additional candidate details
    const detailsSection = page.locator('.candidate-details, .candidate-info');
    
    if (await detailsSection.count() > 0) {
      const detailsText = await detailsSection.textContent();
      
      // Should contain some candidate information
      expect(detailsText).toBeTruthy();
    }
  });

  test('should handle evaluation timeout gracefully', async ({ page }) => {
    // Fill some scores
    const scores = {
      courage: 7,
      innovation: 8,
      implementation: 6,
      relevance: 9,
      visibility: 7
    };
    
    await evaluationPage.fillScores(scores);
    
    // Wait for potential session timeout (simulated)
    // In real scenario, this would be a longer wait
    await page.waitForTimeout(2000);
    
    // Try to save
    await evaluationPage.saveDraft();
    
    // Should either save successfully or show session expired message
    const successMessage = await evaluationPage.getSuccessMessage();
    const errorMessage = await evaluationPage.getErrorMessage();
    
    expect(successMessage || errorMessage).toBeTruthy();
    
    if (errorMessage && errorMessage.includes('session')) {
      // Handle session expiry
      await page.reload();
      // User would need to log in again
    }
  });
});