import { Page, Locator, expect } from '@playwright/test';
import { BasePage } from './base-page';

export class EvaluationFormPage extends BasePage {
  readonly formContainer: Locator;
  readonly candidateName: Locator;
  readonly candidateInfo: Locator;
  
  // Evaluation criteria fields
  readonly courageScore: Locator;
  readonly innovationScore: Locator;
  readonly implementationScore: Locator;
  readonly relevanceScore: Locator;
  readonly visibilityScore: Locator;
  
  // Comment fields
  readonly courageComment: Locator;
  readonly innovationComment: Locator;
  readonly implementationComment: Locator;
  readonly relevanceComment: Locator;
  readonly visibilityComment: Locator;
  readonly generalComment: Locator;
  
  // Action buttons
  readonly saveDraftButton: Locator;
  readonly submitFinalButton: Locator;
  readonly cancelButton: Locator;
  readonly backButton: Locator;
  
  // Progress indicators
  readonly progressBar: Locator;
  readonly totalScore: Locator;
  readonly averageScore: Locator;
  readonly validationErrors: Locator;

  constructor(page: Page) {
    super(page);
    
    this.formContainer = page.locator('.evaluation-form, .mt-evaluation-form, #evaluation-form');
    this.candidateName = page.locator('.candidate-name, h2.candidate-title, .mt-candidate-name');
    this.candidateInfo = page.locator('.candidate-info, .candidate-details, .mt-candidate-info');
    
    // Score inputs (0-10 with 0.5 increments)
    this.courageScore = page.locator('input[name*="courage"], select[name*="courage"], #courage_score');
    this.innovationScore = page.locator('input[name*="innovation"], select[name*="innovation"], #innovation_score');
    this.implementationScore = page.locator('input[name*="implementation"], select[name*="implementation"], #implementation_score');
    this.relevanceScore = page.locator('input[name*="relevance"], select[name*="relevance"], #relevance_score');
    this.visibilityScore = page.locator('input[name*="visibility"], select[name*="visibility"], #visibility_score');
    
    // Comment textareas
    this.courageComment = page.locator('textarea[name*="courage_comment"], #courage_comment');
    this.innovationComment = page.locator('textarea[name*="innovation_comment"], #innovation_comment');
    this.implementationComment = page.locator('textarea[name*="implementation_comment"], #implementation_comment');
    this.relevanceComment = page.locator('textarea[name*="relevance_comment"], #relevance_comment');
    this.visibilityComment = page.locator('textarea[name*="visibility_comment"], #visibility_comment');
    this.generalComment = page.locator('textarea[name*="general_comment"], textarea[name*="comments"], #general_comment');
    
    // Buttons
    this.saveDraftButton = page.locator('button:has-text("Save Draft"), input[value="Save Draft"], .save-draft-btn');
    this.submitFinalButton = page.locator('button:has-text("Submit Final"), input[value="Submit Final"], .submit-final-btn');
    this.cancelButton = page.locator('button:has-text("Cancel"), a:has-text("Cancel"), .cancel-btn');
    this.backButton = page.locator('a:has-text("Back"), button:has-text("Back"), .back-btn');
    
    // Progress and validation
    this.progressBar = page.locator('.progress-bar, .evaluation-progress, .mt-progress');
    this.totalScore = page.locator('.total-score, .score-total, #total-score');
    this.averageScore = page.locator('.average-score, .score-average, #average-score');
    this.validationErrors = page.locator('.validation-error, .error-message, .mt-error');
  }

  /**
   * Fill evaluation scores
   */
  async fillScores(scores: {
    courage: number;
    innovation: number;
    implementation: number;
    relevance: number;
    visibility: number;
  }) {
    // Fill each score field
    await this.setScore(this.courageScore, scores.courage);
    await this.setScore(this.innovationScore, scores.innovation);
    await this.setScore(this.implementationScore, scores.implementation);
    await this.setScore(this.relevanceScore, scores.relevance);
    await this.setScore(this.visibilityScore, scores.visibility);
    
    // Wait for score calculation
    await this.page.waitForTimeout(500);
  }

  /**
   * Set a score value (handles both input and select elements)
   */
  private async setScore(field: Locator, value: number) {
    // Ensure value is in valid range (0-10 with 0.5 increments)
    const validValue = Math.min(10, Math.max(0, Math.round(value * 2) / 2));
    
    const tagName = await field.evaluate(el => el.tagName.toLowerCase());
    
    if (tagName === 'select') {
      await field.selectOption(validValue.toString());
    } else if (tagName === 'input') {
      const inputType = await field.getAttribute('type');
      
      if (inputType === 'range') {
        await field.fill(validValue.toString());
        // Trigger change event for range inputs
        await field.dispatchEvent('change');
      } else if (inputType === 'number') {
        await field.fill(validValue.toString());
      } else {
        // Radio buttons or other input types
        const radioButton = this.page.locator(`input[name="${await field.getAttribute('name')}"][value="${validValue}"]`);
        await radioButton.check();
      }
    }
  }

  /**
   * Fill evaluation comments
   */
  async fillComments(comments: {
    courage?: string;
    innovation?: string;
    implementation?: string;
    relevance?: string;
    visibility?: string;
    general?: string;
  }) {
    if (comments.courage) await this.courageComment.fill(comments.courage);
    if (comments.innovation) await this.innovationComment.fill(comments.innovation);
    if (comments.implementation) await this.implementationComment.fill(comments.implementation);
    if (comments.relevance) await this.relevanceComment.fill(comments.relevance);
    if (comments.visibility) await this.visibilityComment.fill(comments.visibility);
    if (comments.general) await this.generalComment.fill(comments.general);
  }

  /**
   * Submit complete evaluation
   */
  async submitEvaluation(scores: any, comments: any = {}) {
    await this.fillScores(scores);
    await this.fillComments(comments);
    await this.submitFinal();
  }

  /**
   * Save as draft
   */
  async saveDraft() {
    await this.saveDraftButton.click();
    await this.waitForAjax();
    
    // Check for success message
    const successMessage = await this.getSuccessMessage();
    expect(successMessage).toContain('Draft saved');
  }

  /**
   * Submit final evaluation
   */
  async submitFinal() {
    await this.submitFinalButton.click();
    
    // Handle confirmation dialog if it appears
    const confirmDialog = this.page.locator('.confirm-dialog, .swal2-confirm, button:has-text("Confirm")');
    if (await confirmDialog.count() > 0) {
      await confirmDialog.click();
    }
    
    await this.waitForPageLoad();
  }

  /**
   * Get calculated total score
   */
  async getTotalScore(): Promise<number> {
    const scoreText = await this.totalScore.textContent();
    return parseFloat(scoreText?.replace(/[^\d.]/g, '') || '0');
  }

  /**
   * Get calculated average score
   */
  async getAverageScore(): Promise<number> {
    const scoreText = await this.averageScore.textContent();
    return parseFloat(scoreText?.replace(/[^\d.]/g, '') || '0');
  }

  /**
   * Check if form has validation errors
   */
  async hasValidationErrors(): Promise<boolean> {
    return await this.validationErrors.count() > 0;
  }

  /**
   * Get validation error messages
   */
  async getValidationErrors(): Promise<string[]> {
    const errors = await this.validationErrors.all();
    const messages = [];
    
    for (const error of errors) {
      const text = await error.textContent();
      if (text) messages.push(text.trim());
    }
    
    return messages;
  }

  /**
   * Get current progress percentage
   */
  async getProgress(): Promise<number> {
    const progressValue = await this.progressBar.getAttribute('aria-valuenow')
      || await this.progressBar.getAttribute('data-progress')
      || await this.progressBar.locator('.progress-value').textContent();
    
    return parseFloat(progressValue?.replace(/[^\d.]/g, '') || '0');
  }

  /**
   * Check if evaluation is complete
   */
  async isComplete(): Promise<boolean> {
    const progress = await this.getProgress();
    return progress === 100;
  }

  /**
   * Get candidate information
   */
  async getCandidateInfo(): Promise<{
    name: string;
    organization?: string;
    position?: string;
  }> {
    const name = await this.candidateName.textContent();
    const infoText = await this.candidateInfo.textContent();
    
    // Parse organization and position from info text
    const orgMatch = infoText?.match(/Organization:\s*([^|]+)/);
    const posMatch = infoText?.match(/Position:\s*([^|]+)/);
    
    return {
      name: name?.trim() || '',
      organization: orgMatch?.[1]?.trim(),
      position: posMatch?.[1]?.trim()
    };
  }

  /**
   * Verify all required fields are present
   */
  async verifyRequiredFields() {
    await expect(this.courageScore).toBeVisible();
    await expect(this.innovationScore).toBeVisible();
    await expect(this.implementationScore).toBeVisible();
    await expect(this.relevanceScore).toBeVisible();
    await expect(this.visibilityScore).toBeVisible();
  }

  /**
   * Reset form
   */
  async resetForm() {
    const resetButton = this.page.locator('button:has-text("Reset"), .reset-btn');
    if (await resetButton.count() > 0) {
      await resetButton.click();
      
      // Confirm reset if dialog appears
      const confirmReset = this.page.locator('button:has-text("Yes"), button:has-text("Confirm")');
      if (await confirmReset.count() > 0) {
        await confirmReset.click();
      }
    }
  }

  /**
   * Navigate back to dashboard
   */
  async goBack() {
    await this.backButton.click();
    await this.waitForPageLoad();
  }
}